<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@cardswap.it',
                'username' => 'admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'is_verified' => true,
            ],
            [
                'name' => 'Mario Rossi',
                'email' => 'mario.rossi@example.com',
                'username' => 'mario_rossi',
                'password' => bcrypt('password'),
                'role' => 'seller',
                'email_verified_at' => now(),
                'is_verified' => true,
                'bio' => 'Venditore professionale di carte da collezione',
                'rating' => 4.8,
                'total_sales' => 150,
            ],
            [
                'name' => 'Giulia Bianchi',
                'email' => 'giulia.bianchi@example.com',
                'username' => 'giulia_bianchi',
                'password' => bcrypt('password'),
                'role' => 'buyer',
                'email_verified_at' => now(),
                'is_verified' => true,
                'bio' => 'Collezionista appassionata di carte da calcio',
                'total_purchases' => 45,
            ],
        ];

        foreach ($users as $user) {
            \App\Models\User::create($user);
        }
    }
}
