<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardModel;
use App\Models\Category;

class CountCards extends Command
{
    protected $signature = 'cards:count {category?}';
    protected $description = 'Conta le carte per categoria';

    public function handle()
    {
        $categorySlug = $this->argument('category');
        
        if ($categorySlug) {
            $category = Category::where('slug', $categorySlug)->first();
            if (!$category) {
                $this->error("Categoria '{$categorySlug}' non trovata");
                return 1;
            }
            
            $count = CardModel::where('category_id', $category->id)
                ->where('is_active', true)
                ->count();
            
            $this->info("{$category->name}: {$count} carte");
        } else {
            $categories = Category::all();
            foreach ($categories as $category) {
                $count = CardModel::where('category_id', $category->id)
                    ->where('is_active', true)
                    ->count();
                
                $this->line("{$category->name} ({$category->slug}): {$count} carte");
            }
        }
        
        return 0;
    }
}

