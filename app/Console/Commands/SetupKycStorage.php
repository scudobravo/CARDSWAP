<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SetupKycStorage extends Command
{
    protected $signature = 'kyc:setup-storage';
    protected $description = 'Crea la directory di storage per i documenti KYC';

    public function handle(): int
    {
        $this->info('Setting up KYC storage...');

        try {
            // Crea la directory principale
            Storage::disk('kyc')->makeDirectory('documents');
            
            // Crea .gitignore per sicurezza
            $gitignorePath = storage_path('app/kyc/.gitignore');
            file_put_contents($gitignorePath, "*\n!.gitignore\n");

            $this->info('KYC storage directory created successfully.');
            $this->info('Path: ' . storage_path('app/kyc'));
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create KYC storage: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
