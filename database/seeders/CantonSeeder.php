<?php

namespace Database\Seeders;

use App\Models\Canton;
use Illuminate\Database\Seeder;

class CantonSeeder extends Seeder
{
    public function run(): void
    {
        $cantons = [
            ['code' => 'AG', 'name_de' => 'Aargau', 'name_fr' => 'Argovie', 'latitude' => 47.3876664, 'longitude' => 8.2554295],
            ['code' => 'AI', 'name_de' => 'Appenzell Innerrhoden', 'name_fr' => 'Appenzell Rhodes-Intérieures', 'latitude' => 47.3161925, 'longitude' => 9.4316572],
            ['code' => 'AR', 'name_de' => 'Appenzell Ausserrhoden', 'name_fr' => 'Appenzell Rhodes-Extérieures', 'latitude' => 47.3664810, 'longitude' => 9.3000918],
            ['code' => 'BE', 'name_de' => 'Bern', 'name_fr' => 'Berne', 'latitude' => 46.9482713, 'longitude' => 7.4514512],
            ['code' => 'BL', 'name_de' => 'Basel-Landschaft', 'name_fr' => 'Bâle-Campagne', 'latitude' => 47.4414372, 'longitude' => 7.7343170],
            ['code' => 'BS', 'name_de' => 'Basel-Stadt', 'name_fr' => 'Bâle-Ville', 'latitude' => 47.5596010, 'longitude' => 7.5885761],
            ['code' => 'FR', 'name_de' => 'Freiburg', 'name_fr' => 'Fribourg', 'latitude' => 46.8064773, 'longitude' => 7.1619719],
            ['code' => 'GE', 'name_de' => 'Genf', 'name_fr' => 'Genève', 'latitude' => 46.2043907, 'longitude' => 6.1431577],
            ['code' => 'GL', 'name_de' => 'Glarus', 'name_fr' => 'Glaris', 'latitude' => 47.0407101, 'longitude' => 9.0680004],
            ['code' => 'GR', 'name_de' => 'Graubünden', 'name_fr' => 'Grisons', 'latitude' => 46.6569871, 'longitude' => 9.5781597],
            ['code' => 'JU', 'name_de' => 'Jura', 'name_fr' => 'Jura', 'latitude' => 47.3444474, 'longitude' => 7.1430605],
            ['code' => 'LU', 'name_de' => 'Luzern', 'name_fr' => 'Lucerne', 'latitude' => 47.0505452, 'longitude' => 8.3054682],
            ['code' => 'NE', 'name_de' => 'Neuenburg', 'name_fr' => 'Neuchâtel', 'latitude' => 46.9899874, 'longitude' => 6.9292731],
            ['code' => 'NW', 'name_de' => 'Nidwalden', 'name_fr' => 'Nidwald', 'latitude' => 46.9267016, 'longitude' => 8.3849485],
            ['code' => 'OW', 'name_de' => 'Obwalden', 'name_fr' => 'Obwald', 'latitude' => 46.8778582, 'longitude' => 8.2512490],
            ['code' => 'SG', 'name_de' => 'St. Gallen', 'name_fr' => 'Saint-Gall', 'latitude' => 47.4244818, 'longitude' => 9.3767173],
            ['code' => 'SH', 'name_de' => 'Schaffhausen', 'name_fr' => 'Schaffhouse', 'latitude' => 47.6958292, 'longitude' => 8.6380483],
            ['code' => 'SO', 'name_de' => 'Solothurn', 'name_fr' => 'Soleure', 'latitude' => 47.2088348, 'longitude' => 7.5322910],
            ['code' => 'SZ', 'name_de' => 'Schwyz', 'name_fr' => 'Schwytz', 'latitude' => 47.0207138, 'longitude' => 8.6529884],
            ['code' => 'TG', 'name_de' => 'Thurgau', 'name_fr' => 'Thurgovie', 'latitude' => 47.5537136, 'longitude' => 9.0068029],
            ['code' => 'TI', 'name_de' => 'Tessin', 'name_fr' => 'Tessin', 'latitude' => 46.0036758, 'longitude' => 8.9510520],
            ['code' => 'UR', 'name_de' => 'Uri', 'name_fr' => 'Uri', 'latitude' => 46.7739869, 'longitude' => 8.6025153],
            ['code' => 'VD', 'name_de' => 'Waadt', 'name_fr' => 'Vaud', 'latitude' => 46.5619715, 'longitude' => 6.5367650],
            ['code' => 'VS', 'name_de' => 'Wallis', 'name_fr' => 'Valais', 'latitude' => 46.1908141, 'longitude' => 7.6128410],
            ['code' => 'ZG', 'name_de' => 'Zug', 'name_fr' => 'Zoug', 'latitude' => 47.1661500, 'longitude' => 8.5154950],
            ['code' => 'ZH', 'name_de' => 'Zürich', 'name_fr' => 'Zurich', 'latitude' => 47.3768866, 'longitude' => 8.5416940],
        ];

        foreach ($cantons as $canton) {
            Canton::updateOrCreate(['code' => $canton['code']], $canton);
        }
    }
}
