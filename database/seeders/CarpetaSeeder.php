<?php

namespace Database\Seeders;

use App\Models\Carpeta;
use Illuminate\Database\Seeder;

class CarpetaSeeder extends Seeder
{
    public function run(): void
    {
        // Carpetas principales
        $documentos = Carpeta::create([
            'nombre' => 'Documentos Generales',
            'slug' => 'documentos-generales',
            'descripcion' => 'Carpeta para documentos y archivos generales',
            'color' => '#3B82F6',
            'icono' => 'folder',
            'orden' => 1,
            'activa' => true
        ]);

        $premium = Carpeta::create([
            'nombre' => 'Recursos Premium',
            'slug' => 'recursos-premium',
            'descripcion' => 'Contenido exclusivo para usuarios premium',
            'color' => '#F59E0B',
            'icono' => 'star',
            'orden' => 2,
            'activa' => true
        ]);

        $tutoriales = Carpeta::create([
            'nombre' => 'Tutoriales',
            'slug' => 'tutoriales',
            'descripcion' => 'Guías y tutoriales paso a paso',
            'color' => '#10B981',
            'icono' => 'book-open',
            'orden' => 3,
            'activa' => true
        ]);

        $plantillas = Carpeta::create([
            'nombre' => 'Plantillas',
            'slug' => 'plantillas',
            'descripcion' => 'Plantillas y formatos reutilizables',
            'color' => '#8B5CF6',
            'icono' => 'file-text',
            'orden' => 4,
            'activa' => true
        ]);

        // Subcarpetas de Documentos Generales
        Carpeta::create([
            'nombre' => 'Contratos',
            'slug' => 'contratos',
            'descripcion' => 'Modelos de contratos y documentos legales',
            'color' => '#EF4444',
            'icono' => 'file-text',
            'orden' => 1,
            'activa' => true,
            'padre_id' => $documentos->id
        ]);

        Carpeta::create([
            'nombre' => 'Formularios',
            'slug' => 'formularios',
            'descripcion' => 'Formularios y solicitudes',
            'color' => '#06B6D4',
            'icono' => 'clipboard',
            'orden' => 2,
            'activa' => true,
            'padre_id' => $documentos->id
        ]);

        // Subcarpetas de Tutoriales
        Carpeta::create([
            'nombre' => 'Básicos',
            'slug' => 'tutoriales-basicos',
            'descripcion' => 'Tutoriales para principiantes',
            'color' => '#84CC16',
            'icono' => 'play-circle',
            'orden' => 1,
            'activa' => true,
            'padre_id' => $tutoriales->id
        ]);

        Carpeta::create([
            'nombre' => 'Avanzados',
            'slug' => 'tutoriales-avanzados',
            'descripcion' => 'Tutoriales para usuarios avanzados',
            'color' => '#F97316',
            'icono' => 'zap',
            'orden' => 2,
            'activa' => true,
            'padre_id' => $tutoriales->id
        ]);

        // Subcarpetas de Plantillas
        Carpeta::create([
            'nombre' => 'Word',
            'slug' => 'plantillas-word',
            'descripcion' => 'Plantillas para Microsoft Word',
            'color' => '#2563EB',
            'icono' => 'file-text',
            'orden' => 1,
            'activa' => true,
            'padre_id' => $plantillas->id
        ]);

        Carpeta::create([
            'nombre' => 'Excel',
            'slug' => 'plantillas-excel',
            'descripcion' => 'Plantillas para Microsoft Excel',
            'color' => '#16A34A',
            'icono' => 'table',
            'orden' => 2,
            'activa' => true,
            'padre_id' => $plantillas->id
        ]);
    }
}