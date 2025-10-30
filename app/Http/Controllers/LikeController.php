<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Swipe;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LikeController extends Controller
{
    /**
     * List likes.
     * Returns a paginated list of persons the current user liked, with optional mutual-only filter.
     * 
     * @group Matches
     * @authenticated
     * 
     * @queryParam page integer The page number. Defaults to 1. Example: 1
     * @queryParam limit integer The page size. Defaults to 20. Example: 20
     * @queryParam mutual_only boolean Filter to only mutual likes. Defaults to false. Example: true
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'mutual_only' => ['sometimes', 'boolean'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 20);
        $mutualOnly = (bool) ($validated['mutual_only'] ?? false);

        $user = $request->user();
        $userPerson = method_exists($user, 'person') ? $user->person()->first() : null;

        // Likes by current user
        $likesQuery = Swipe::query()
            ->where('swiper_user_id', $user->id)
            ->where('action', 'like');

        $total = (clone $likesQuery)->count();
        $likes = $likesQuery->forPage($page, $limit)->get();

        // Map to persons and compute mutual
        $persons = collect();
        foreach ($likes as $like) {
            $p = Person::find($like->target_person_id);
            if (! $p) {
                continue;
            }
            $isMutual = false;
            if ($userPerson && $p->user_id) {
                $isMutual = Swipe::where('swiper_user_id', $p->user_id)
                    ->where('target_person_id', $userPerson->id)
                    ->where('action', 'like')
                    ->exists();
            }
            $persons->push([
                'person' => $p,
                'is_mutual' => $isMutual,
            ]);
        }

        if ($mutualOnly) {
            $persons = $persons->filter(fn ($row) => $row['is_mutual'])->values();
        }

        $paginator = new LengthAwarePaginator(
            $persons->values(),
            $mutualOnly ? $persons->count() : $total,
            $limit,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
            ],
        ]);
    }
}