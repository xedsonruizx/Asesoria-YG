<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TagCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TagCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            [
                'name' => 'Legal',
                'slug' => 'legal',
                'description' => 'Contenido relacionado con temas legales, normativas y regulaciones',
                'color' => '#DC2626', // Rojo
                'is_active' => true,
            ],
            [
                'name' => 'RRHH',
                'slug' => 'rrhh',
                'description' => 'Contenido sobre recursos humanos, gestión de personal y políticas laborales',
                'color' => '#059669', // Verde
                'is_active' => true,
            ],
        ];

        foreach ($tags as $tagData) {
            // Usar DB::table para evitar problemas con timestamps
            DB::table('tags_category')->updateOrInsert(
                ['slug' => $tagData['slug']], // Condición de búsqueda
                array_merge($tagData, [
                    'created_at' => DB::raw('NOW()'),
                    'updated_at' => DB::raw('NOW()'),
                ])
            );
        }

        $this->command->info('Tags de categoría creados correctamente.');
    }
}