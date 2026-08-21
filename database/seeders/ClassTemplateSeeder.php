<?php

namespace Database\Seeders;

use App\Models\ClassTemplate;
use App\Models\Discipline;
use Illuminate\Database\Seeder;

class ClassTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'road-race' => [
                ['name' => 'Sport 150', 'slug' => 'sport-150', 'level_order' => 10],
                ['name' => 'Sport 250', 'slug' => 'sport-250', 'level_order' => 20],
                ['name' => 'Underbone 150', 'slug' => 'underbone-150', 'level_order' => 30],
                ['name' => 'Bebek 4T', 'slug' => 'bebek-4t', 'level_order' => 40],
                ['name' => 'Open', 'slug' => 'open', 'level_order' => 100],
            ],
            'enduro' => [
                ['name' => 'Enduro 2T', 'slug' => 'enduro-2t', 'level_order' => 20],
                ['name' => 'Enduro 4T', 'slug' => 'enduro-4t', 'level_order' => 30],
                ['name' => 'Trail', 'slug' => 'trail', 'level_order' => 40],
                ['name' => 'Veteran', 'slug' => 'veteran', 'level_order' => 50],
                ['name' => 'Open', 'slug' => 'open', 'level_order' => 100],
            ],
            'motocross' => [
                ['name' => 'MX1', 'slug' => 'mx1', 'level_order' => 30],
                ['name' => 'MX2', 'slug' => 'mx2', 'level_order' => 20],
                ['name' => 'MX65', 'slug' => 'mx65', 'level_order' => 10],
                ['name' => 'MX85', 'slug' => 'mx85', 'level_order' => 15],
                ['name' => 'Rookie', 'slug' => 'rookie', 'level_order' => 25],
                ['name' => 'Open', 'slug' => 'open', 'level_order' => 100],
            ],
            'grasstrack' => [
                ['name' => 'Novice', 'slug' => 'novice', 'level_order' => 20],
                ['name' => 'Beginner', 'slug' => 'beginner', 'level_order' => 30],
                ['name' => 'Expert', 'slug' => 'expert', 'level_order' => 50],
                ['name' => 'Com-A', 'slug' => 'com-a', 'level_order' => 40],
                ['name' => 'Com-B', 'slug' => 'com-b', 'level_order' => 45],
                ['name' => 'Open', 'slug' => 'open', 'level_order' => 100],
            ],
        ];

        foreach ($templates as $disciplineSlug => $classTemplates) {
            $discipline = Discipline::where('slug', $disciplineSlug)->first();

            if (! $discipline) {
                continue;
            }

            foreach ($classTemplates as $template) {
                ClassTemplate::firstOrCreate(
                    [
                        'discipline_id' => $discipline->id,
                        'slug' => $template['slug'],
                    ],
                    [
                        'name' => $template['name'],
                        'level_order' => $template['level_order'],
                    ]
                );
            }
        }
    }
}
