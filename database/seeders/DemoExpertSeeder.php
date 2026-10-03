<?php

namespace Database\Seeders;

use App\Models\Canton;
use App\Models\Expert;
use App\Models\ExpertOffice;
use App\Models\User;
use App\Support\ExpertStatus;
use App\Support\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoExpertSeeder extends Seeder
{
    public function run(): void
    {
        $demos = [
            [
                'company' => 'Alpine Nachfolge Partner AG',
                'city' => 'Zürich',
                'postal' => '8001',
                'address' => 'Bahnhofstrasse 12',
                'canton' => 'ZH',
                'buy' => true,
                'sell' => true,
                'email' => 'demo.zh@nachfolge-experten.local',
                'lat' => 47.3768866,
                'lng' => 8.5416940,
            ],
            [
                'company' => 'Léman Corporate Finance',
                'city' => 'Genève',
                'postal' => '1204',
                'address' => 'Rue du Rhône 48',
                'canton' => 'GE',
                'buy' => true,
                'sell' => false,
                'email' => 'demo.ge@nachfolge-experten.local',
                'lat' => 46.2043907,
                'lng' => 6.1431577,
            ],
            [
                'company' => 'Mittelland M&A Beratung',
                'city' => 'Bern',
                'postal' => '3011',
                'address' => 'Kramgasse 7',
                'canton' => 'BE',
                'buy' => false,
                'sell' => true,
                'email' => 'demo.be@nachfolge-experten.local',
                'lat' => 46.9482713,
                'lng' => 7.4514512,
            ],
            [
                'company' => 'Sarnen Succession Desk',
                'city' => 'Sarnen',
                'postal' => '6060',
                'address' => 'Brünigstrasse 144',
                'canton' => 'OW',
                'buy' => true,
                'sell' => true,
                'email' => 'demo.ow@nachfolge-experten.local',
                'lat' => 46.8962910,
                'lng' => 8.2456430,
            ],
            [
                'company' => 'Basel Deal Advisory',
                'city' => 'Basel',
                'postal' => '4051',
                'address' => 'Freie Strasse 20',
                'canton' => 'BS',
                'buy' => true,
                'sell' => true,
                'email' => 'demo.bs@nachfolge-experten.local',
                'lat' => 47.5596010,
                'lng' => 7.5885761,
            ],
        ];

        foreach ($demos as $demo) {
            $canton = Canton::query()->where('code', $demo['canton'])->first();
            if (! $canton) {
                continue;
            }

            $user = User::query()->firstOrCreate(
                ['email' => $demo['email']],
                [
                    'name' => $demo['company'],
                    'password' => Hash::make('ExpertDemo!123'),
                    'role' => Role::EXPERT,
                    'is_active' => true,
                ]
            );

            $expert = Expert::query()->updateOrCreate(
                ['email' => $demo['email']],
                [
                    'user_id' => $user->id,
                    'company_name' => $demo['company'],
                    'slug' => Str::slug($demo['company']),
                    'phone' => '+41 41 000 00 00',
                    'description' => 'Demo-Profil für die Plattformvorschau. Spezialisiert auf Schweizer KMU-Nachfolge.',
                    'services_text' => 'Unternehmensbewertung, Mandatsbegleitung, Käufersuche',
                    'offers_buy' => $demo['buy'],
                    'offers_sell' => $demo['sell'],
                    'status' => ExpertStatus::APPROVED,
                    'is_public' => true,
                    'contact_person_name' => 'Demo',
                    'contact_person_last_name' => 'Berater',
                ]
            );

            ExpertOffice::query()->updateOrCreate(
                [
                    'expert_id' => $expert->id,
                    'is_primary' => true,
                ],
                [
                    'label' => 'Hauptsitz',
                    'address_line' => $demo['address'],
                    'postal_code' => $demo['postal'],
                    'city' => $demo['city'],
                    'canton_id' => $canton->id,
                    'latitude' => $demo['lat'],
                    'longitude' => $demo['lng'],
                    'sort_order' => 0,
                ]
            );
        }
    }
}
