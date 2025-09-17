<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Evaluación Premium',
                'description' => 'Acceso completo a evaluaciones avanzadas con reportes detallados',
                'price' => 29990,
                'currency' => 'CLP',
                'category' => 'evaluacion',
                'duration_days' => 30,
                'features' => [
                    'Evaluaciones ilimitadas',
                    'Reportes PDF detallados',
                    'Análisis comparativo',
                    'Soporte prioritario'
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Consultoría Personalizada',
                'description' => 'Sesión de consultoría uno a uno con expertos',
                'price' => 89990,
                'currency' => 'CLP',
                'category' => 'consultoria',
                'duration_days' => 7,
                'features' => [
                    'Sesión de 2 horas',
                    'Plan de acción personalizado',
                    'Seguimiento por email',
                    'Materiales exclusivos'
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Curso Online Completo',
                'description' => 'Acceso completo a todos los cursos y materiales',
                'price' => 149990,
                'currency' => 'CLP',
                'category' => 'educacion',
                'duration_days' => 365,
                'features' => [
                    'Acceso por 1 año',
                    '20+ horas de contenido',
                    'Certificado de finalización',
                    'Actualizaciones gratuitas'
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Pago de Multa',
                'description' => 'Pago de multa por incumplimiento normativo',
                'price' => 50000,
                'currency' => 'CLP',
                'category' => 'multa',
                'duration_days' => null,
                'features' => [
                    'Pago único',
                    'Comprobante digital',
                    'Registro en historial'
                ],
                'sort_order' => 4,
                'is_active' => true,
            ]
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}