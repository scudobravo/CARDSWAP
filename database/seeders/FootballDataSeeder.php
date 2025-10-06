<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\League;
use App\Models\Team;
use App\Models\Player;
use App\Models\CardSet;
use App\Models\GradingCompany;

class FootballDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creo le aziende di grading dal CSV di Alessia
        $gradingCompanies = [
            ['name' => 'PSA', 'slug' => 'psa', 'description' => 'Professional Sports Authenticator'],
            ['name' => 'BGS', 'slug' => 'bgs', 'description' => 'Beckett Grading Services'],
            ['name' => 'AIGRADING', 'slug' => 'aigrading', 'description' => 'AI Grading Services'],
            ['name' => 'GRAAD', 'slug' => 'graad', 'description' => 'GRAAD Grading Services'],
            ['name' => 'CGC', 'slug' => 'cgc', 'description' => 'Certified Guaranty Company'],
        ];

        foreach ($gradingCompanies as $company) {
            GradingCompany::updateOrCreate(['slug' => $company['slug']], $company);
        }

        // Creo le leghe
        $leagues = [
            ['name' => 'Serie A', 'slug' => 'serie-a', 'country' => 'Italia', 'sort_order' => 1],
            ['name' => 'Premier League', 'slug' => 'premier-league', 'country' => 'Inghilterra', 'sort_order' => 2],
            ['name' => 'La Liga', 'slug' => 'la-liga', 'country' => 'Spagna', 'sort_order' => 3],
            ['name' => 'Bundesliga', 'slug' => 'bundesliga', 'country' => 'Germania', 'sort_order' => 4],
            ['name' => 'Ligue 1', 'slug' => 'ligue-1', 'country' => 'Francia', 'sort_order' => 5],
        ];

        foreach ($leagues as $league) {
            League::updateOrCreate(['slug' => $league['slug']], $league);
        }

        // Creo le squadre per la Serie A
        $serieA = League::where('slug', 'serie-a')->first();
        $teams = [
            ['name' => 'Juventus', 'slug' => 'juventus', 'city' => 'Torino', 'primary_color' => '#000000', 'secondary_color' => '#FFFFFF'],
            ['name' => 'Inter', 'slug' => 'inter', 'city' => 'Milano', 'primary_color' => '#0000FF', 'secondary_color' => '#000000'],
            ['name' => 'Milan', 'slug' => 'milan', 'city' => 'Milano', 'primary_color' => '#FF0000', 'secondary_color' => '#000000'],
            ['name' => 'Napoli', 'slug' => 'napoli', 'city' => 'Napoli', 'primary_color' => '#0000FF', 'secondary_color' => '#FFFFFF'],
            ['name' => 'Roma', 'slug' => 'roma', 'city' => 'Roma', 'primary_color' => '#FF0000', 'secondary_color' => '#FFFF00'],
        ];

        foreach ($teams as $team) {
            $team['league_id'] = $serieA->id;
            Team::updateOrCreate(['slug' => $team['slug']], $team);
        }

        // Creo i giocatori per alcune squadre
        $juventus = Team::where('slug', 'juventus')->first();
        $inter = Team::where('slug', 'inter')->first();
        $milan = Team::where('slug', 'milan')->first();

        $players = [
            // Juventus
            ['name' => 'Federico Chiesa', 'slug' => 'federico-chiesa', 'position' => 'Attaccante', 'nationality' => 'Italia', 'team_id' => $juventus->id],
            ['name' => 'Dusan Vlahovic', 'slug' => 'dusan-vlahovic', 'position' => 'Attaccante', 'nationality' => 'Serbia', 'team_id' => $juventus->id],
            ['name' => 'Paul Pogba', 'slug' => 'paul-pogba', 'position' => 'Centrocampista', 'nationality' => 'Francia', 'team_id' => $juventus->id],
            
            // Inter
            ['name' => 'Lautaro Martinez', 'slug' => 'lautaro-martinez', 'position' => 'Attaccante', 'nationality' => 'Argentina', 'team_id' => $inter->id],
            ['name' => 'Marcus Thuram', 'slug' => 'marcus-thuram', 'position' => 'Attaccante', 'nationality' => 'Francia', 'team_id' => $inter->id],
            ['name' => 'Nicolò Barella', 'slug' => 'nicolo-barella', 'position' => 'Centrocampista', 'nationality' => 'Italia', 'team_id' => $inter->id],
            
            // Milan
            ['name' => 'Rafael Leão', 'slug' => 'rafael-leao', 'position' => 'Attaccante', 'nationality' => 'Portogallo', 'team_id' => $milan->id],
            ['name' => 'Olivier Giroud', 'slug' => 'olivier-giroud', 'position' => 'Attaccante', 'nationality' => 'Francia', 'team_id' => $milan->id],
            ['name' => 'Theo Hernandez', 'slug' => 'theo-hernandez', 'position' => 'Difensore', 'nationality' => 'Francia', 'team_id' => $milan->id],
        ];

        foreach ($players as $player) {
            Player::updateOrCreate(['slug' => $player['slug']], $player);
        }

        // Creo i set di carte
        $calcioCategory = \App\Models\Category::where('slug', 'calcio')->first();
        $cardSets = [
            ['name' => 'Panini Calciatori 2024/25', 'slug' => 'panini-calciatori-2024-25', 'brand' => 'Panini', 'year' => 2024, 'season' => '2024/25', 'total_cards' => 600],
            ['name' => 'Panini Calciatori 2023/24', 'slug' => 'panini-calciatori-2023-24', 'brand' => 'Panini', 'year' => 2023, 'season' => '2023/24', 'total_cards' => 600],
            ['name' => 'Topps Chrome Serie A 2024', 'slug' => 'topps-chrome-serie-a-2024', 'brand' => 'Topps', 'year' => 2024, 'season' => '2024/25', 'total_cards' => 300],
            ['name' => 'Upper Deck Serie A 2024', 'slug' => 'upper-deck-serie-a-2024', 'brand' => 'Upper Deck', 'year' => 2024, 'season' => '2024/25', 'total_cards' => 200],
        ];

        foreach ($cardSets as $set) {
            $set['category_id'] = $calcioCategory->id;
            CardSet::updateOrCreate(['slug' => $set['slug']], $set);
        }
    }
}
