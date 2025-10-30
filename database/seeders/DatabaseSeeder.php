<?php

namespace Database\Seeders;

use App\Models\User;
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
        User::firstOrCreate(
            ['email' => 'lovehunter@tinder-clone.com'],
            [
                'name' => 'Love Hunter',
                // Password casting in User model will hash this value
                'password' => 'password',
            ]
        );
    }
}
