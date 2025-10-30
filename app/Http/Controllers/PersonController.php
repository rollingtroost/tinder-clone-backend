<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    /**
     * Upsert profile.
     * Create or update the authenticated user's Person profile.
     * 
     * @group Profile
     * @authenticated
     * 
     * @bodyParam name string required The display name.
     * @bodyParam age integer required The age (minimum 18).
     * @bodyParam pictures array required Between 1 and 6 image URLs.
     * @bodyParam pictures.* string required An image URL.
     * @bodyParam latitude number required Latitude between -90 and 90.
     * @bodyParam longitude number required Longitude between -180 and 180.
     * @bodyParam bio string optional A short bio (max 255 characters).
     * @bodyParam city string optional The city (max 255 characters).
     */
    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'age' => ['required', 'integer', 'min:18'],
            'pictures' => ['required', 'array', 'min:1', 'max:6'],
            'pictures.*' => ['required', 'string', 'url'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'bio' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $person = $user->person()->first();

        if ($person) {
            $person->update([
                'name' => $validated['name'],
                'age' => $validated['age'],
                'pictures' => $validated['pictures'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'bio' => $validated['bio'] ?? null,
            ]);
        } else {
            $person = Person::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'age' => $validated['age'],
                'pictures' => $validated['pictures'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'bio' => $validated['bio'] ?? null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'person' => $person,
        ], 201);
    }
}