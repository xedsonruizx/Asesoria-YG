<?php

namespace Database\Seeders;

use App\Models\Multa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MultaSeeder extends Seeder
{
    public function run(): void
    {
        $multas = [
            [
                'name' => 'Multa por Incumplimiento Laboral',
                'description' => 'Multa aplicada por no cumplir con las normativas laborales vigentes.',
                'file_path' => 'multas/multa-laboral-001.pdf',
                'is_active' => true,
            ],
            [
                'name' => 'Multa Tributaria',
                'description' => 'Multa por declaración tardía de impuestos.',
                'file_path' => 'multas/multa-tributaria-001.pdf',
                'is_active' => true,
            ],
            [
                'name' => 'Multa Ambiental',
                'description' => 'Multa por incumplimiento de normativas ambientales.',
                'file_path' => 'multas/multa-ambiental-001.pdf',
                'is_active' => true,
            ],
            [
                'name' => 'Multa Pagada',
                'description' => 'Multa por incumplimiento menor ya pagada.',
                'file_path' => 'multas/multa-administrativa-002.pdf',
                'is_active' => true,
            ],
            [
                'name' => 'Multa Inactiva',
                'description' => 'Multa archivada o cancelada.',
                'file_path' => null,
                'is_active' => false,
            ],
        ];

        foreach ($multas as $multa) {
            DB::table('multas')->insert(array_merge($multa, [
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
            ]));
        }

        $this->command->info('Multas creadas correctamente.');
    }
}