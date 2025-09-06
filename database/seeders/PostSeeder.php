<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar la tabla antes de insertar
        DB::table('posts')->truncate();

        $posts = [
            [
                'title' => 'Guía Completa de Compliance Legal para Empresas',
                'content' => 'Una guía exhaustiva sobre cómo implementar un sistema de compliance efectivo en tu empresa. Incluye templates, checklists y casos de estudio reales de empresas que han logrado certificaciones internacionales. Aprenderás sobre marcos normativos, evaluación de riesgos, implementación de controles y monitoreo continuo.',
                'Category' => 'Legal',
                'status' => 'published',
                'image_path' => 'posts/legal-compliance.jpg',
                'file_path' => 'posts/compliance-guide.pdf',
                'Subscripcion' => true,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
            [
                'title' => 'Estrategias de Reclutamiento Digital',
                'content' => 'Descubre las mejores prácticas para atraer talento en la era digital. Desde la optimización de ofertas de trabajo hasta el uso de redes sociales profesionales y plataformas especializadas. Incluye métricas clave, herramientas recomendadas y casos de éxito.',
                'Category' => 'RRHH',
                'status' => 'published',
                'image_path' => 'posts/reclutamiento-digital.jpg',
                'file_path' => null,
                'Subscripcion' => false,
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(25),
            ],
            [
                'title' => 'Contratos Laborales: Tipos y Consideraciones Legales',
                'content' => 'Análisis detallado de los diferentes tipos de contratos laborales, sus implicaciones legales y mejores prácticas para su redacción. Incluye modelos de contratos, cláusulas especiales y consideraciones fiscales.',
                'Category' => 'Legal',
                'status' => 'published',
                'image_path' => 'posts/contratos-laborales.jpg',
                'file_path' => 'posts/modelos-contratos.zip',
                'Subscripcion' => true,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
            [
                'title' => 'Evaluación de Desempeño: Metodologías Modernas',
                'content' => 'Explora las metodologías más efectivas para evaluar el desempeño de tus empleados. Desde sistemas tradicionales hasta enfoques ágiles como OKRs y feedback continuo. Incluye plantillas y herramientas digitales.',
                'Category' => 'RRHH',
                'status' => 'published',
                'image_path' => 'posts/evaluacion-desempeno.jpg',
                'file_path' => 'posts/plantillas-evaluacion.xlsx',
                'Subscripcion' => false,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],
            [
                'title' => 'Protección de Datos Personales en el Ámbito Laboral',
                'content' => 'Guía completa sobre el manejo de datos personales de empleados conforme a la normativa vigente. Incluye políticas de privacidad, consentimientos, derechos ARCO y medidas de seguridad.',
                'Category' => 'Legal',
                'status' => 'draft',
                'image_path' => null,
                'file_path' => null,
                'Subscripcion' => true,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(5),
            ],
            [
                'title' => 'Cultura Organizacional: Construcción y Mantenimiento',
                'content' => 'Aprende a construir y mantener una cultura organizacional sólida que impulse el compromiso y la productividad. Incluye diagnósticos, planes de acción y métricas de seguimiento.',
                'Category' => 'RRHH',
                'status' => 'published',
                'image_path' => 'posts/cultura-organizacional.jpg',
                'file_path' => null,
                'Subscripcion' => false,
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
            [
                'title' => 'Resolución de Conflictos Laborales',
                'content' => 'Estrategias y técnicas para la resolución efectiva de conflictos en el ámbito laboral. Desde la mediación hasta los procedimientos legales, con casos prácticos y recomendaciones.',
                'Category' => 'Legal',
                'status' => 'published',
                'image_path' => 'posts/conflictos-laborales.jpg',
                'file_path' => 'posts/guia-mediacion.pdf',
                'Subscripcion' => true,
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
            [
                'title' => 'Compensaciones y Beneficios: Diseño de Paquetes Atractivos',
                'content' => 'Diseña paquetes de compensaciones competitivos que atraigan y retengan talento. Incluye análisis de mercado, estructuras salariales y beneficios innovadores.',
                'Category' => 'RRHH',
                'status' => 'draft',
                'image_path' => null,
                'file_path' => null,
                'Subscripcion' => false,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Auditorías Laborales: Preparación y Ejecución',
                'content' => 'Guía paso a paso para preparar y ejecutar auditorías laborales efectivas. Incluye checklists, documentación requerida y mejores prácticas para evitar contingencias.',
                'Category' => 'Legal',
                'status' => 'published',
                'image_path' => 'posts/auditorias-laborales.jpg',
                'file_path' => 'posts/checklist-auditoria.pdf',
                'Subscripcion' => true,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'title' => 'Transformación Digital en RRHH',
                'content' => 'Explora cómo la tecnología está transformando la gestión de recursos humanos. Desde sistemas HRIS hasta inteligencia artificial en reclutamiento y análisis predictivo.',
                'Category' => 'RRHH',
                'status' => 'published',
                'image_path' => 'posts/transformacion-digital-rrhh.jpg',
                'file_path' => null,
                'Subscripcion' => false,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ];

        foreach ($posts as $post) {
            Post::create($post);
        }

        $this->command->info('Posts seeder ejecutado correctamente. Se crearon ' . count($posts) . ' publicaciones.');
    }
}