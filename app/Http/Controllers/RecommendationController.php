<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RecommendationController extends Controller
{
    /**
     * Get recommendations.
     * Returns a paginated list of recommended persons sorted by proximity and compatibility.
     * 
     * @group Recommendations
     * @authenticated
     * 
     * @queryParam page integer The page number. Defaults to 1. Example: 1
     * @queryParam limit integer The page size. Defaults to 20. Example: 20
     * @queryParam lat number Required if user has no saved location. Latitude of the user's location. Example: -6.2000
     * @queryParam lng number Required if user has no saved location. Longitude of the user's location. Example: 106.8166
     * @queryParam age integer Optional current user's age to improve compatibility scoring. Example: 25
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'age' => ['nullable', 'integer', 'min:18', 'max:120'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 20);

        $user = $request->user();

        // Resolve user's location
        $lat = $validated['lat'] ?? null;
        $lng = $validated['lng'] ?? null;

        // If user has an associated person with location, default to that
        $userPerson = null;
        if (method_exists($user, 'person')) {
            $userPerson = $user->person()->first();
            if ($userPerson && $userPerson->latitude !== null && $userPerson->longitude !== null) {
                $lat = $lat ?? $userPerson->latitude;
                $lng = $lng ?? $userPerson->longitude;
            }
        }

        // Require lat/lng if we still don't have it
        if ($lat === null || $lng === null) {
            return response()->json([
                'message' => 'Latitude and longitude are required (either in query or saved profile).',
            ], 422);
        }

        $userAge = $validated['age'] ?? ($userPerson?->age ?? null);

        // Build base query of persons excluding user's own profile if exists
        $query = Person::query();
        if ($userPerson) {
            $query->where('id', '!=', $userPerson->id);
        }

        // Prefer SQL Haversine on drivers that support trig functions; fallback to PHP for sqlite
        $driver = $query->getModel()->getConnection()->getDriverName();
        if ($driver !== 'sqlite') {
            // Compute distance using Haversine formula (in kilometers)
            $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

            $query->select('*')
                ->selectRaw("$haversine as distance_km", [$lat, $lng, $lat])
                // Use COALESCE to avoid Postgres indeterminate parameter type when checking NULL
                ->selectRaw('COALESCE(1 - LEAST(ABS(age - ?), 50)/50.0, 0.5) as compatibility_score', [$userAge]);

            // Sorting strategy depends on DB driver behavior with NULLs and alias usage
            if ($driver === 'pgsql') {
                // Postgres: use NULLS LAST explicitly and avoid alias-in-expression issues
                $query->orderByRaw('distance_km ASC NULLS LAST')
                      ->orderByDesc('compatibility_score');
            } else {
                // MySQL/others: push NULL distances last via boolean ordering, then by distance and compatibility
                $query->orderByRaw('distance_km IS NULL ASC')
                      ->orderBy('distance_km')
                      ->orderByDesc('compatibility_score');
            }

            $total = (clone $query)->count();
            $items = $query->forPage($page, $limit)->get();

            $items->transform(function (Person $p) {
                $p->pictures = $p->pictures ?? [];
                return $p;
            });

            $paginator = new LengthAwarePaginator($items, $total, $limit, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        } else {
            // Fallback: compute distances and compatibility in PHP
            $persons = $query->get();
            $calc = function (Person $p) use ($lat, $lng, $userAge) {
                $distance = null;
                if ($p->latitude !== null && $p->longitude !== null) {
                    $distance = $this->haversineKm($lat, $lng, $p->latitude, $p->longitude);
                }
                $compat = $userAge === null ? 0.5 : (1 - min(abs($p->age - $userAge), 50) / 50.0);
                return [$distance, $compat];
            };

            $persons = $persons->map(function (Person $p) use ($calc) {
                [$d, $c] = $calc($p);
                $p->distance_km = $d;
                $p->compatibility_score = $c;
                $p->pictures = $p->pictures ?? [];
                return $p;
            })->sort(function ($a, $b) {
                // Sort by distance ascending, then compatibility descending; null distances last
                if ($a->distance_km === null && $b->distance_km !== null) return 1;
                if ($a->distance_km !== null && $b->distance_km === null) return -1;
                if ($a->distance_km !== null && $b->distance_km !== null) {
                    if ($a->distance_km < $b->distance_km) return -1;
                    if ($a->distance_km > $b->distance_km) return 1;
                }
                if ($a->compatibility_score === $b->compatibility_score) return 0;
                return $a->compatibility_score > $b->compatibility_score ? -1 : 1;
            });

            $total = $persons->count();
            $items = $persons->slice(($page - 1) * $limit, $limit)->values();
            $paginator = new LengthAwarePaginator($items, $total, $limit, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        }

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
            ],
        ]);
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}