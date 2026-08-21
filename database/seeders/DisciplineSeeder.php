<?php

namespace Database\Seeders;

use App\Models\Discipline;
use Illuminate\Database\Seeder;

class DisciplineSeeder extends Seeder
{
    public function run(): void
    {
        $disciplines = [
            ['name' => 'Road Race', 'slug' => 'road-race'],
            ['name' => 'Enduro', 'slug' => 'enduro'],
            ['name' => 'Motocross', 'slug' => 'motocross'],
            ['name' => 'Grasstrack', 'slug' => 'grasstrack'],
        ];

        foreach ($disciplines as $discipline) {
            Discipline::firstOrCreate(
                ['slug' => $discipline['slug']],
                ['name' => $discipline['name']]
            );
        }
    }
}
