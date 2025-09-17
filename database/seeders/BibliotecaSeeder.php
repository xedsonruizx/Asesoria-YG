<?php

namespace Database\Seeders;

use App\Models\Biblioteca;
use Illuminate\Database\Seeder;

class BibliotecaSeeder extends Seeder
{
    public function run(): void
    {
        // Crear elementos padre
        $programacion = Biblioteca::create([
            'titulo' => 'Programación',
            'slug' => 'programacion',
            'descripcion' => '<h1>Programación</h1><p>Aprende los fundamentos de la programación.</p>',
            'orden' => 1,
            'is_premium' => false
        ]);

        $webDev = Biblioteca::create([
            'titulo' => 'Desarrollo Web',
            'slug' => 'desarrollo-web',
            'descripcion' => '<h1>Desarrollo Web</h1><p>Todo sobre desarrollo web moderno.</p>',
            'orden' => 2,
            'is_premium' => true
        ]);

        // Crear elementos hijos
        Biblioteca::create([
            'titulo' => 'JavaScript Básico',
            'slug' => 'javascript-basico',
            'descripcion' => '<h1>JavaScript Básico</h1><p>Fundamentos de JavaScript.</p>',
            'padre_id' => $programacion->id,
            'orden' => 1,
            'is_premium' => false
        ]);

        Biblioteca::create([
            'titulo' => 'PHP Avanzado',
            'slug' => 'php-avanzado',
            'descripcion' => '<h1>PHP Avanzado</h1><p>Conceptos avanzados de PHP.</p>',
            'padre_id' => $programacion->id,
            'orden' => 2,
            'is_premium' => true
        ]);
    }
}