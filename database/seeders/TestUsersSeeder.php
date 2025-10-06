<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea utente normale (può comprare e vendere)
        User::updateOrCreate(
            ['email' => 'user@test.com'],
            [
                'name' => 'Test User',
                'username' => 'testuser',
                'password' => Hash::make('password'),
                'role' => 'buyer',
                'kyc_status' => 'pending',
                'is_verified' => true,
            ]
        );

        // Crea utente con KYC approvato
        User::updateOrCreate(
            ['email' => 'verified@test.com'],
            [
                'name' => 'Verified User',
                'username' => 'verifieduser',
                'password' => Hash::make('password'),
                'role' => 'buyer',
                'kyc_status' => 'approved',
                'is_verified' => true,
            ]
        );

        // Crea utente admin
        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'username' => 'testadmin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'kyc_status' => 'approved',
                'is_verified' => true,
            ]
        );

        $this->command->info('Test users created successfully!');
        $this->command->info('User (KYC pending): user@test.com / password');
        $this->command->info('User (KYC approved): verified@test.com / password');
        $this->command->info('Admin: admin@test.com / password');
    }
}
