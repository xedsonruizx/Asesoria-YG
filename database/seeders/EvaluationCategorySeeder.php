<?php

namespace Database\Seeders;

use App\Models\EvaluationCategory;
use Illuminate\Database\Seeder;

class EvaluationCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Recursos Humanos',
                'slug' => 'rrhh',
                'description' => 'Evaluación de políticas y procedimientos de recursos humanos',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Legal',
                'slug' => 'legal',
                'description' => 'Evaluación de cumplimiento legal y normativo',
                'color' => '#10B981',
            ],
            [
                'name' => 'Financiero',
                'slug' => 'financiero',
                'description' => 'Evaluación de aspectos financieros y contables',
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Operacional',
                'slug' => 'operacional',
                'description' => 'Evaluación de procesos operacionales',
                'color' => '#8B5CF6',
            ],
        ];

        foreach ($categories as $category) {
            EvaluationCategory::create($category);
        }
    }
}