<?php

namespace App\Http\Controllers;

use App\Http\Resources\VerificationResource;
use App\Http\Resources\CredentialResource;
use App\Models\Verification;
use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class VerificationController extends Controller
{
    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can create verifications'], 403);
        }

        $validated = $request->validate([
            'credential_id' => ['required', 'exists:credentials,id'],
            'status'        => ['nullable', 'in:pending,approved,rejected'],
            'notes'         => ['nullable', 'string'],
        ]);

        $credential = Credential::with('verification')->findOrFail($validated['credential_id']);

        $status = $validated['status'] ?? 'pending';
        $notes  = $validated['notes'] ?? null;

        // Ako već postoji verifikacija – update; u suprotnom – create
        if ($credential->verification) {
            $credential->verification->update([
                'status' => $status,
                'notes'  => $notes,
            ]);
            $verification = $credential->verification->fresh();
        } else {
            $verification = Verification::create([
                'credential_id' => $credential->id,
                'status'        => $status,
                'notes'         => $notes,
            ]);
        }

        $credential->is_verified = ($verification->status === 'approved');
        $credential->save();

        $credential->load(['category', 'verification', 'user']);

        return response()->json([
            'message'      => 'Verification saved successfully',
            'verification' => new VerificationResource($verification),
            'credential'   => new CredentialResource($credential),
        ], 201);
    }

    public function credentialsByStatus(string $status, Request $request)
    {
        $allowed = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            return response()->json(['error' => 'Invalid status'], 422);
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $page    = (int) $request->input('page', 1);

        $query = Credential::query()
            ->with(['category', 'verification', 'user'])
            ->whereHas('verification', fn($q) => $q->where('status', $status))
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        if ($paginator->total() === 0) {
            return response()->json('No credentials found for given status.', 404);
        }

        return response()->json([
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'status'       => $status,
            ],
            'credentials' => \App\Http\Resources\CredentialResource::collection(
                collect($paginator->items())
            ),
        ]);
    }
}
