<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RorController extends Controller
{
    /**
     * GET /api/issuers/ror/verify?query=MIT
     *
     * Šaljemo SAMO ?query= ka ROR v2 /organizations.
     * Lokalno normalizujemo i izračunamo "best_guess".
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:200'],
        ]);

        $q   = trim($validated['query']);
        $ttl = (int) config('services.ror.cache_ttl_minutes', 720);

        $cacheKey = 'ror:v2:verify:' . md5($q);

        $payload = Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($q) {
            $base    = rtrim((string) config('services.ror.base_url', 'https://api.ror.org/v2'), '/');
            $timeout = (int) config('services.ror.timeout', 8);

            // SAMO query param
            $resp = Http::timeout($timeout)
                ->acceptJson()
                ->get($base . '/organizations', ['query' => $q]);

            if (!$resp->ok()) {
                return ['error' => 'ror_unavailable', 'status' => $resp->status()];
            }

            $data  = $resp->json();
            $items = (array)($data['items'] ?? []);

            // Normalizacija polja
            $normalized = array_map(function ($it) {
                $displayName = null;
                foreach (($it['names'] ?? []) as $n) {
                    $types = $n['types'] ?? [];
                    if (in_array('ror_display', $types, true) || in_array('label', $types, true)) {
                        $displayName = $n['value'] ?? null;
                        break;
                    }
                }
                if (!$displayName && !empty($it['names'][0]['value'])) {
                    $displayName = $it['names'][0]['value'];
                }

                // country (iz prve lokacije ako postoji)
                $geo = $it['locations'][0]['geonames_details'] ?? [];

                // spakuj najčešće tražene eksterne ID-eve
                $ext = ['grid' => null, 'isni' => null, 'wikidata' => null];
                foreach (($it['external_ids'] ?? []) as $eid) {
                    $type = $eid['type'] ?? null;
                    $pref = $eid['preferred'] ?? null;
                    if (isset($ext[$type])) {
                        $ext[$type] = $pref ?: (($eid['all'][0] ?? null) ?? null);
                    }
                }

                return [
                    'ror_id'        => $it['id'] ?? null,
                    'name'          => $displayName,
                    'acronyms'      => array_values(array_map(
                        fn($n) => $n['value'] ?? null,
                        array_filter($it['names'] ?? [], fn($n) => in_array('acronym', $n['types'] ?? [], true))
                    )),
                    'types'         => $it['types'] ?? [],
                    'status'        => $it['status'] ?? null,
                    'established'   => $it['established'] ?? null,
                    'country_code'  => $geo['country_code'] ?? null,
                    'country_name'  => $geo['country_name'] ?? null,
                    'city'          => $geo['name'] ?? null,
                    'links'         => $it['links'] ?? [],
                    'domains'       => $it['domains'] ?? [],
                    'external_ids'  => $ext,
                ];
            }, $items);

            // Jednostavan "best guess":
            // 1) tačno poklapanje imena (case-insensitive)
            // 2) poklapanje akronima
            // 3) fallback na prvi rezultat
            $bestIndex = null;
            $needle    = mb_strtolower($q);

            foreach ($normalized as $i => $row) {
                if ($row['name'] && mb_strtolower($row['name']) === $needle) {
                    $bestIndex = $i;
                    break;
                }
            }
            if ($bestIndex === null) {
                foreach ($normalized as $i => $row) {
                    if (in_array(strtoupper($q), array_map('strtoupper', $row['acronyms'] ?? []), true)) {
                        $bestIndex = $i;
                        break;
                    }
                }
            }
            if ($bestIndex === null && count($normalized) > 0) {
                $bestIndex = 0;
            }

            return [
                'number_of_results' => (int)($data['number_of_results'] ?? count($normalized)),
                'items'             => $normalized,
                'best_guess_index'  => $bestIndex,
            ];
        });

        if (isset($payload['error'])) {
            return response()->json($payload, 503);
        }

        if (empty($payload['items'])) {
            return response()->json('No organizations found on ROR for given query.', 404);
        }

        $best = $payload['best_guess_index'] !== null
            ? $payload['items'][$payload['best_guess_index']]
            : null;

        return response()->json([
            'query'              => $q,
            'number_of_results'  => $payload['number_of_results'],
            'best_guess'         => $best,
            'items'              => $payload['items'],
        ]);
    }
}
