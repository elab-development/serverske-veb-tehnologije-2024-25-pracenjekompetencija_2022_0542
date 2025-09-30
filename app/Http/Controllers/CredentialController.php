<?php

namespace App\Http\Controllers;

use App\Http\Resources\CredentialResource;
use App\Models\Credential;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CredentialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allowedSorts = ['id', 'title', 'issued_at', 'expires_at', 'created_at'];
        $sortBy  = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : 'id';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $perPage = (int) ($request->input('per_page', 15));
        $perPage = max(1, min(100, $perPage));
        $page    = (int) ($request->input('page', 1));

        $search  = $request->input('search', $request->input('q'));
        $today   = Carbon::today()->toDateString();

        $query = Credential::query()
            ->with(['category', 'verification', 'user'])
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $uid = $request->user_id;
                if ($uid === 'me' && Auth::check()) {
                    $uid = Auth::id();
                }
                $q->where('user_id', $uid);
            })
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('is_verified'), function ($q) use ($request) {
                $val = filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (!is_null($val)) $q->where('is_verified', $val);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->whereHas('verification', fn($qq) => $qq->where('status', $request->status));
            })
            ->when($request->filled('issued_from'), fn($q) => $q->whereDate('issued_at', '>=', request('issued_from')))
            ->when($request->filled('issued_to'), fn($q) => $q->whereDate('issued_at', '<=', request('issued_to')))
            ->when($request->filled('expires_from'), fn($q) => $q->whereDate('expires_at', '>=', request('expires_from')))
            ->when($request->filled('expires_to'), fn($q) => $q->whereDate('expires_at', '<=', request('expires_to')))
            ->when($request->filled('expired'), function ($q) use ($request, $today) {
                $val = filter_var($request->expired, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($val === true) {
                    // istekli do danas (expires_at < today)
                    $q->whereNotNull('expires_at')->whereDate('expires_at', '<', $today);
                } elseif ($val === false) {
                    // ne-istekli (nema expires ili je u budućnosti)
                    $q->where(function ($qq) use ($today) {
                        $qq->whereNull('expires_at')
                            ->orWhereDate('expires_at', '>=', $today);
                    });
                }
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('title', 'like', "%{$search}%")
                        ->orWhere('issuer_name', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortBy, $sortDir);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        if ($paginator->total() === 0) {
            return response()->json('No credentials found.', 404);
        }

        return response()->json([
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'sort_by'      => $sortBy,
                'sort_dir'     => $sortDir,
            ],
            'credentials' => CredentialResource::collection(collect($paginator->items())),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $validated = $request->validate([
            'user_id'     => ['required', 'exists:users,id'],
            'title'       => ['required', 'string', 'max:180'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'issuer_name' => ['nullable', 'string', 'max:160'],
            'issued_at'   => ['nullable', 'date'],
            'expires_at'  => ['nullable', 'date', 'after_or_equal:issued_at'],
            'is_verified' => ['sometimes', 'boolean'], // biće ignorisan ako nije admin
        ]);

        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        if (!$isAdmin && (int)$validated['user_id'] !== (int)$user->id) {
            return response()->json(['error' => 'You can create credentials only for yourself'], 403);
        }

        if (!$isAdmin) {
            unset($validated['is_verified']); // korisnik to ne postavlja direktno
        }

        $credential = Credential::create($validated)->load(['category', 'verification', 'user']);

        return response()->json([
            'message'    => 'Credential created successfully',
            'credential' => new CredentialResource($credential),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Credential $credential)
    {
        $credential->load(['category', 'verification']);

        return response()->json([
            'credential' => new CredentialResource($credential),
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Credential $credential)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Credential $credential)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        if (!$isAdmin && (int)$credential->user_id !== (int)$user->id) {
            return response()->json(['error' => 'You can update only your own credentials'], 403);
        }

        $validated = $request->validate([
            'user_id'     => ['sometimes', 'required', 'exists:users,id'],
            'title'       => ['sometimes', 'required', 'string', 'max:180'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'issuer_name' => ['sometimes', 'nullable', 'string', 'max:160'],
            'issued_at'   => ['sometimes', 'nullable', 'date'],
            'expires_at'  => ['sometimes', 'nullable', 'date', 'after_or_equal:issued_at'],
            'is_verified' => ['sometimes', 'boolean'], // samo admin
        ]);

        if (!$isAdmin) {
            unset($validated['user_id'], $validated['is_verified']);
        }

        $credential->update($validated);
        $credential->load(['category', 'verification', 'user']);

        return response()->json([
            'message'    => 'Credential updated successfully',
            'credential' => new CredentialResource($credential),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Credential $credential)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        if (!$isAdmin && (int)$credential->user_id !== (int)$user->id) {
            return response()->json(['error' => 'You can delete only your own credentials'], 403);
        }

        $credential->delete();

        return response()->json(['message' => 'Credential deleted successfully']);
    }
}
