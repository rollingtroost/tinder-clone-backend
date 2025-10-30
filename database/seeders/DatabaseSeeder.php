<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Person;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed a default user for local development and testing
        $user = User::firstOrCreate(
            ['email' => 'lovehunter@tinder-clone.com'],
            [
                'name' => 'Love Hunter',
                // Password casting in User model will hash this value
                'password' => 'password',
            ]
        );

        // Create a Person profile for the default user using the factory
        Person::factory()->state([
            'user_id' => $user->id,
        ])->create();

        // Create 500 additional users, each with an associated Person profile
        User::factory()
            ->count(499)
            ->has(Person::factory())
            ->create();
    }
}
