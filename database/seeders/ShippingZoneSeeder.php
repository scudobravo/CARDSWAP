<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingZone;

class ShippingZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shippingZones = [
            // ITALIA - Spedizione standard
            [
                'name' => 'Italia - Spedizione Standard',
                'country_code' => 'IT',
                'region' => null,
                'city' => null,
                'postal_code' => null,
                'shipping_cost' => 3.50,
                'delivery_days_min' => 2,
                'delivery_days_max' => 4,
                'is_active' => true
            ],
            // ITALIA - Spedizione tracciata
            [
                'name' => 'Italia - Spedizione Tracciata',
                'country_code' => 'IT',
                'region' => null,
                'city' => null,
                'postal_code' => null,
                'shipping_cost' => 5.50,
                'delivery_days_min' => 1,
                'delivery_days_max' => 3,
                'is_active' => true
            ],
            // UNIONE EUROPEA - Spedizione standard
            [
                'name' => 'Unione Europea - Spedizione Standard',
                'country_code' => 'EU',
                'region' => null,
                'city' => null,
                'postal_code' => null,
                'shipping_cost' => 8.50,
                'delivery_days_min' => 5,
                'delivery_days_max' => 10,
                'is_active' => true
            ],
            // UNIONE EUROPEA - Spedizione tracciata
            [
                'name' => 'Unione Europea - Spedizione Tracciata',
                'country_code' => 'EU',
                'region' => null,
                'city' => null,
                'postal_code' => null,
                'shipping_cost' => 12.50,
                'delivery_days_min' => 3,
                'delivery_days_max' => 7,
                'is_active' => true
            ],
            // EXTRA-UE - Spedizione standard
            [
                'name' => 'Extra Unione Europea - Spedizione Standard',
                'country_code' => 'WW',
                'region' => null,
                'city' => null,
                'postal_code' => null,
                'shipping_cost' => 15.00,
                'delivery_days_min' => 10,
                'delivery_days_max' => 20,
                'is_active' => true
            ],
            // EXTRA-UE - Spedizione tracciata
            [
                'name' => 'Extra Unione Europea - Spedizione Tracciata',
                'country_code' => 'WW',
                'region' => null,
                'city' => null,
                'postal_code' => null,
                'shipping_cost' => 25.00,
                'delivery_days_min' => 7,
                'delivery_days_max' => 15,
                'is_active' => true
            ]
        ];

        foreach ($shippingZones as $zone) {
            ShippingZone::create($zone);
        }
    }
}
