<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@hostiqo.local',
            'password' => Hash::make('password'),
        ]);

        // Create demo user
        User::create([
            'name' => 'Demo User',
            'email' => 'demo@hostiqo.local',
            'password' => Hash::make('password'),
        ]);
    }
}
