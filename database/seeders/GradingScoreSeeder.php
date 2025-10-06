<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GradingScore;
use App\Models\GradingCompany;

class GradingScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ottengo le aziende di grading
        $psa = GradingCompany::where('slug', 'psa')->first();
        $bgs = GradingCompany::where('slug', 'bgs')->first();
        $aigrading = GradingCompany::where('slug', 'aigrading')->first();
        $graad = GradingCompany::where('slug', 'graad')->first();
        $cgc = GradingCompany::where('slug', 'cgc')->first();

        // Creo i voti grading basati sul CSV di Alessia - ALLINEAMENTO PERFETTO
        $gradingScores = [
            // Voti 10 (solo BGS ha Black Label Pristine)
            ['score' => 10.0, 'description' => 'Black Label Pristine (solo BGS)', 'short_code' => 'BL-P', 'is_special' => true, 'grading_company_id' => $bgs->id, 'sort_order' => 1],
            ['score' => 10.0, 'description' => 'Pristine', 'short_code' => 'P', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 2],
            
            // Voti 9.x
            ['score' => 9.5, 'description' => 'Gem Mint', 'short_code' => 'GM', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 3],
            ['score' => 9.0, 'description' => 'Mint', 'short_code' => 'M', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 4],
            
            // Voti 8.x
            ['score' => 8.5, 'description' => 'NM-MT+ (Near Mint-Mint+)', 'short_code' => 'NM-MT+', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 5],
            ['score' => 8.0, 'description' => 'NM-MT (Near Mint-Mint)', 'short_code' => 'NM-MT', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 6],
            
            // Voti 7.x
            ['score' => 7.5, 'description' => 'Near Mint+', 'short_code' => 'NM+', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 7],
            ['score' => 7.0, 'description' => 'Near Mint', 'short_code' => 'NM', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 8],
            
            // Voti 6.x - CORRETTO per allineamento CSV
            ['score' => 6.5, 'description' => '- EX-MT+ (Excellent-Mint+)', 'short_code' => 'EX-MT+', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 9],
            ['score' => 6.0, 'description' => 'EX-MT (Excellent-Mint)', 'short_code' => 'EX-MT', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 10],
            
            // Voti 5.x - CORRETTO per allineamento CSV
            ['score' => 5.5, 'description' => '- Excellent+', 'short_code' => 'EX+', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 11],
            ['score' => 5.0, 'description' => 'Excellent', 'short_code' => 'EX', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 12],
            
            // Voti 4.x
            ['score' => 4.5, 'description' => 'VG-EX+ (Very Good-Excellent+)', 'short_code' => 'VG-EX+', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 13],
            ['score' => 4.0, 'description' => 'VG-EX (Very Good-Excellent)', 'short_code' => 'VG-EX', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 14],
            
            // Voti 3.x
            ['score' => 3.5, 'description' => 'Very Good+', 'short_code' => 'VG+', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 15],
            ['score' => 3.0, 'description' => 'Very Good', 'short_code' => 'VG', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 16],
            
            // Voti 2.x
            ['score' => 2.5, 'description' => 'G-VG (Good-Very Good)', 'short_code' => 'G-VG', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 17],
            ['score' => 2.0, 'description' => 'Good', 'short_code' => 'G', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 18],
            
            // Voti 1.x
            ['score' => 1.5, 'description' => 'Fair', 'short_code' => 'F', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 19],
            ['score' => 1.0, 'description' => 'Poor', 'short_code' => 'P', 'is_special' => false, 'grading_company_id' => null, 'sort_order' => 20],
        ];

        foreach ($gradingScores as $score) {
            GradingScore::create($score);
        }
    }
}
