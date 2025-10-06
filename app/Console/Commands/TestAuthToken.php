<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class TestAuthToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:auth-token {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test auth token for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found");
            return 1;
        }
        
        // Crea un token di test
        $token = $user->createToken('test-token')->plainTextToken;
        
        $this->info("Test token created for user: {$user->name} ({$user->email})");
        $this->info("Token: {$token}");
        $this->info("\nYou can test the API with:");
        $this->info("curl -H 'Authorization: Bearer {$token}' -H 'Accept: application/json' http://localhost:8000/api/addresses");
        
        return 0;
    }
}