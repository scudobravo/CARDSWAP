<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\CardModel;
use App\Models\Category;
use App\Models\CardSet;
use App\Models\Player;
use App\Models\Team;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        $sitemap .= $this->addUrl(url('/'), '1.0', 'daily');
        
        // Catalogo principale
        $sitemap .= $this->addUrl(url('/catalog'), '0.9', 'daily');
        
        // Categorie
        $categories = Category::where('is_active', true)->get();
        foreach ($categories as $category) {
            $sitemap .= $this->addUrl(
                url('/catalog/' . $category->slug),
                '0.8',
                'weekly'
            );
        }
        
        // Set di carte (primi 100 per performance)
        $cardSets = CardSet::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
            
        foreach ($cardSets as $set) {
            $sitemap .= $this->addUrl(
                url('/catalog/set/' . $set->slug),
                '0.7',
                'weekly'
            );
        }
        
        // Giocatori popolari (primi 50 per performance)
        $players = Player::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
            
        foreach ($players as $player) {
            $sitemap .= $this->addUrl(
                url('/catalog/player/' . $player->slug),
                '0.6',
                'monthly'
            );
        }
        
        // Squadre popolari (primi 30 per performance)
        $teams = Team::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();
            
        foreach ($teams as $team) {
            $sitemap .= $this->addUrl(
                url('/catalog/team/' . $team->slug),
                '0.6',
                'monthly'
            );
        }
        
        // Carte popolari (primi 200 per performance)
        $cards = CardModel::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();
            
        foreach ($cards as $card) {
            $sitemap .= $this->addUrl(
                url('/catalog/card/' . $card->slug),
                '0.5',
                'monthly'
            );
        }
        
        // Pagine statiche
        $staticPages = [
            ['url' => url('/privacy-policy'), 'priority' => '0.3', 'changefreq' => 'monthly'],
            ['url' => url('/terms-of-service'), 'priority' => '0.3', 'changefreq' => 'monthly'],
            ['url' => url('/login'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => url('/register'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => url('/help'), 'priority' => '0.4', 'changefreq' => 'monthly'],
            ['url' => url('/contact'), 'priority' => '0.4', 'changefreq' => 'monthly'],
            ['url' => url('/sell'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['url' => url('/seller-guide'), 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];
        
        foreach ($staticPages as $page) {
            $sitemap .= $this->addUrl($page['url'], $page['priority'], $page['changefreq']);
        }

        $sitemap .= '</urlset>';

        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml');
    }

    private function addUrl($url, $priority, $changefreq)
    {
        return sprintf(
            "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>\n",
            htmlspecialchars($url),
            date('Y-m-d'),
            $changefreq,
            $priority
        );
    }
}
