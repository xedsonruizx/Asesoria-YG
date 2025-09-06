<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TagCategory;
use Illuminate\Support\Str;

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
            TagCategory::updateOrCreate(
                ['slug' => $tagData['slug']], // Buscar por slug
                $tagData // Datos a insertar o actualizar
            );
        }
    }
}