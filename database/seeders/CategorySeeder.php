<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Calcio',
                'slug' => 'calcio',
                'description' => 'Carte da collezione di calcio',
                'icon' => '⚽',
                'color' => '#1e40af',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basketball',
                'slug' => 'basketball',
                'description' => 'Carte da collezione di basketball',
                'icon' => '🏀',
                'color' => '#dc2626',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Baseball',
                'slug' => 'baseball',
                'description' => 'Carte da collezione di baseball',
                'icon' => '⚾',
                'color' => '#059669',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Hockey',
                'slug' => 'hockey',
                'description' => 'Carte da collezione di hockey',
                'icon' => '🏒',
                'color' => '#7c3aed',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Altri Sport',
                'slug' => 'altri-sport',
                'description' => 'Carte da collezione di altri sport',
                'icon' => '🏃',
                'color' => '#ea580c',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
