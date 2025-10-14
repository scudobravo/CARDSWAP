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
        // Pulisci le zone esistenti (senza user_id per evitare conflitti)
        ShippingZone::whereNull('user_id')->delete();

        $shippingZones = [
            // ZONA MONDIALE
            [
                'name' => 'Tutto il mondo',
                'country_code' => 'WW',
                'zone_type' => 'worldwide',
                'is_worldwide' => true,
                'excluded_countries' => ['CN', 'MN'],
                'shipping_cost' => 15.00, // Prezzo fallback
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 7,
                'delivery_days_max' => 21,
                'is_active' => true,
                'sort_order' => 1,
                'description' => 'Spedizione in tutto il mondo (esclusi Cina e Mongolia)'
            ],

            // EUROPA
            [
                'name' => 'Europa',
                'country_code' => 'EU',
                'zone_type' => 'continent',
                'included_countries' => [
                    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 
                    'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'CH', 'NO', 'IS', 
                    'LI', 'MC', 'SM', 'VA', 'AD', 'GB'
                ],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 3,
                'delivery_days_max' => 7,
                'is_active' => true,
                'sort_order' => 2,
                'description' => 'Spedizione in tutti i paesi europei'
            ],

            // ASIA
            [
                'name' => 'Asia',
                'country_code' => 'AS',
                'zone_type' => 'continent',
                'included_countries' => [
                    'AF', 'BH', 'BD', 'BT', 'BN', 'KH', 'IN', 'ID', 'IR', 'IQ', 'IL', 'JP', 'JO', 'KZ', 'KW', 
                    'KG', 'LA', 'LB', 'MY', 'MV', 'NP', 'KP', 'KR', 'SA', 'SG', 'LK', 'SY', 'TW', 'TJ', 'TH', 
                    'TL', 'TR', 'TM', 'AE', 'UZ', 'VN', 'YE'
                ],
                'excluded_countries' => ['CN', 'MN'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 7,
                'delivery_days_max' => 14,
                'is_active' => true,
                'sort_order' => 3,
                'description' => 'Spedizione in Asia (esclusi Cina e Mongolia)'
            ],

            // AMERICA
            [
                'name' => 'America',
                'country_code' => 'AM',
                'zone_type' => 'continent',
                'included_countries' => [
                    'AR', 'BS', 'BB', 'BZ', 'BO', 'BR', 'CA', 'CL', 'CO', 'CR', 'CU', 'DM', 'DO', 'EC', 'SV', 
                    'GD', 'GT', 'GY', 'HT', 'HN', 'JM', 'MX', 'NI', 'PA', 'PY', 'PE', 'KN', 'LC', 'VC', 'SR', 
                    'TT', 'US', 'UY', 'VE'
                ],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 7,
                'delivery_days_max' => 21,
                'is_active' => true,
                'sort_order' => 4,
                'description' => 'Spedizione in Nord e Sud America'
            ],

            // STATI UNITI
            [
                'name' => 'Stati Uniti',
                'country_code' => 'US',
                'zone_type' => 'country',
                'included_countries' => ['US'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 5,
                'delivery_days_max' => 10,
                'is_active' => true,
                'sort_order' => 5,
                'description' => 'Spedizione negli Stati Uniti'
            ],

            // UNIONE EUROPEA
            [
                'name' => 'Unione Europea',
                'country_code' => 'EU',
                'zone_type' => 'region',
                'included_countries' => [
                    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 
                    'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'
                ],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 2,
                'delivery_days_max' => 5,
                'is_active' => true,
                'sort_order' => 6,
                'description' => 'Spedizione nell\'Unione Europea'
            ],

            // GIAPPONE
            [
                'name' => 'Giappone',
                'country_code' => 'JP',
                'zone_type' => 'country',
                'included_countries' => ['JP'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 5,
                'delivery_days_max' => 10,
                'is_active' => true,
                'sort_order' => 7,
                'description' => 'Spedizione in Giappone'
            ],

            // CANADA
            [
                'name' => 'Canada',
                'country_code' => 'CA',
                'zone_type' => 'country',
                'included_countries' => ['CA'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 7,
                'delivery_days_max' => 14,
                'is_active' => true,
                'sort_order' => 8,
                'description' => 'Spedizione in Canada'
            ],

            // AUSTRALIA
            [
                'name' => 'Australia',
                'country_code' => 'AU',
                'zone_type' => 'country',
                'included_countries' => ['AU'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 10,
                'delivery_days_max' => 21,
                'is_active' => true,
                'sort_order' => 9,
                'description' => 'Spedizione in Australia'
            ],

            // FRANCIA
            [
                'name' => 'Francia',
                'country_code' => 'FR',
                'zone_type' => 'country',
                'included_countries' => ['FR'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 2,
                'delivery_days_max' => 4,
                'is_active' => true,
                'sort_order' => 10,
                'description' => 'Spedizione in Francia'
            ],

            // SPAGNA
            [
                'name' => 'Spagna',
                'country_code' => 'ES',
                'zone_type' => 'country',
                'included_countries' => ['ES'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 2,
                'delivery_days_max' => 4,
                'is_active' => true,
                'sort_order' => 11,
                'description' => 'Spedizione in Spagna'
            ],

            // FEDERAZIONE RUSSA
            [
                'name' => 'Federazione Russa',
                'country_code' => 'RU',
                'zone_type' => 'country',
                'included_countries' => ['RU'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 7,
                'delivery_days_max' => 14,
                'is_active' => true,
                'sort_order' => 12,
                'description' => 'Spedizione in Russia'
            ],

            // REGNO UNITO
            [
                'name' => 'Regno Unito',
                'country_code' => 'GB',
                'zone_type' => 'country',
                'included_countries' => ['GB'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 3,
                'delivery_days_max' => 7,
                'is_active' => true,
                'sort_order' => 13,
                'description' => 'Spedizione nel Regno Unito'
            ],

            // AUSTRIA
            [
                'name' => 'Austria',
                'country_code' => 'AT',
                'zone_type' => 'country',
                'included_countries' => ['AT'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 2,
                'delivery_days_max' => 4,
                'is_active' => true,
                'sort_order' => 14,
                'description' => 'Spedizione in Austria'
            ],

            // ITALIA
            [
                'name' => 'Italia',
                'country_code' => 'IT',
                'zone_type' => 'country',
                'included_countries' => ['IT'],
                'use_shippo_pricing' => true,
                'shippo_service_type' => 'standard',
                'shippo_markup' => 1.60,
                'delivery_days_min' => 1,
                'delivery_days_max' => 3,
                'is_active' => true,
                'sort_order' => 0,
                'description' => 'Spedizione in Italia'
            ],
        ];

        foreach ($shippingZones as $zone) {
            ShippingZone::create($zone);
        }
    }
}