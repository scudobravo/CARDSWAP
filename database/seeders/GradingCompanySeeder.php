<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GradingCompany;
use Illuminate\Support\Str;

class GradingCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lista delle grading companies più comuni
        $gradingCompanies = [
            [
                'name' => 'PSA',
                'slug' => 'psa',
                'description' => 'Professional Sports Authenticator',
                'website_url' => 'https://www.psacard.com',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'BGS',
                'slug' => 'bgs',
                'description' => 'Beckett Grading Services',
                'website_url' => 'https://www.beckett.com/grading',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'CGC',
                'slug' => 'cgc',
                'description' => 'Certified Guaranty Company',
                'website_url' => 'https://www.cgccards.com',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'SGC',
                'slug' => 'sgc',
                'description' => 'Sportscard Guaranty Corporation',
                'website_url' => 'https://sgccard.com',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'AIGRADING',
                'slug' => 'aigrading',
                'description' => 'AI Grading Services',
                'website_url' => null,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'GRAAD',
                'slug' => 'graad',
                'description' => 'GRAAD Grading Services',
                'website_url' => null,
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'HGA',
                'slug' => 'hga',
                'description' => 'Hybrid Grading Approach',
                'website_url' => 'https://www.hgagrading.com',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'CSG',
                'slug' => 'csg',
                'description' => 'Certified Sports Guaranty',
                'website_url' => 'https://www.csgcards.com',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($gradingCompanies as $company) {
            // Usa updateOrCreate per evitare duplicati basandosi sullo slug
            GradingCompany::updateOrCreate(
                ['slug' => $company['slug']],
                $company
            );
        }

        $this->command->info('✓ Grading companies seeded successfully!');
    }
}

