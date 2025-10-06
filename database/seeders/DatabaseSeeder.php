<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            UserSeeder::class,
            FootballDataSeeder::class, // Aggiungo il seeder per i dati calcio
            GradingScoreSeeder::class, // Aggiungo il seeder per i voti grading
        ]);
    }
}
