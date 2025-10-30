<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Swipe;
use Illuminate\Http\Request;

class SwipeController extends Controller
{
    /**
     * Swipe action.
     * Accepts {person_id, action: 'like'|'dislike'} and records the swipe.
     * 
     * @group Interactions
     * @authenticated
     * 
     * @bodyParam person_id int required The target person's ID. Example: 12
     * @bodyParam action string required The swipe action ('like' or 'dislike'). Example: like
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'action' => ['required', 'string', 'in:like,dislike'],
        ]);

        $person = Person::findOrFail($validated['person_id']);
        $user = $request->user();

        // Upsert latest swipe for (user, person)
        $swipe = Swipe::where('swiper_user_id', $user->id)
                      ->where('target_person_id', $person->id)
                      ->first();

        if ($swipe) {
            $swipe->action = $validated['action'];
            $swipe->save();
        } else {
            $swipe = Swipe::create([
                'swiper_user_id' => $user->id,
                'target_person_id' => $person->id,
                'action' => $validated['action'],
            ]);
        }

        // Notification: if like pushes the person over 50 likes, queue an admin email
        if ($swipe->action === 'like') {
            $likeCount = Swipe::where('target_person_id', $person->id)
                ->where('action', 'like')
                ->count();
            if ($likeCount > 50 && $person->popular_notified_at === null) {
                // fire job and mark as notified
                \App\Jobs\SendPopularPersonNotification::dispatch($person, $likeCount);
                $person->popular_notified_at = now();
                $person->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'swipe' => $swipe,
        ], 201);
    }
}