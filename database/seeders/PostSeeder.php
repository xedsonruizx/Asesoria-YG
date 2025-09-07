<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\TagCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Deshabilitar verificaciones de claves foráneas temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Limpiar las tablas relacionadas
        DB::table('post_tag_category')->delete();
        Post::query()->delete();
        
        // Rehabilitar verificaciones de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Obtener las categorías de tags
        $legalTag = TagCategory::where('slug', 'legal')->first();
        $rrhhTag = TagCategory::where('slug', 'rrhh')->first();

        // Verificar que las categorías existan
        if (!$legalTag || !$rrhhTag) {
            $this->command->error('Las categorías de tags no existen. Ejecuta primero TagCategorySeeder.');
            return;
        }

        $posts = [
            [
                'title' => 'Guía Completa de Compliance Legal para Empresas',
                'slug' => Str::slug('Guía Completa de Compliance Legal para Empresas'),
                'content' => 'Una guía exhaustiva sobre cómo implementar un sistema de compliance efectivo en tu empresa. Incluye templates, checklists y casos de estudio reales de empresas que han logrado certificaciones internacionales. Aprenderás sobre marcos normativos, evaluación de riesgos, implementación de controles y monitoreo continuo.',
                'excerpt' => 'Guía exhaustiva sobre implementación de sistemas de compliance efectivos en empresas.',
                'meta_description' => 'Aprende a implementar un sistema de compliance efectivo con templates, checklists y casos de estudio reales.',
                'status' => 'published',
                'is_premium' => true,
                'image_path' => 'posts/legal-compliance.jpg',
                'file_path' => 'posts/compliance-guide.pdf',
                'author_id' => 1,
                'published_at' => DB::raw('DATE_SUB(NOW(), INTERVAL 30 DAY)'),
                'created_at' => DB::raw('DATE_SUB(NOW(), INTERVAL 30 DAY)'),
                'updated_at' => DB::raw('DATE_SUB(NOW(), INTERVAL 30 DAY)'),
                'tag_categories' => [$legalTag->id]
            ],
            [
                'title' => 'Estrategias de Reclutamiento Digital',
                'slug' => Str::slug('Estrategias de Reclutamiento Digital'),
                'content' => 'Descubre las mejores prácticas para atraer talento en la era digital. Desde la optimización de ofertas de trabajo hasta el uso de redes sociales profesionales y plataformas especializadas. Incluye métricas clave, herramientas recomendadas y casos de éxito.',
                'excerpt' => 'Mejores prácticas para atraer talento en la era digital con herramientas y métricas clave.',
                'meta_description' => 'Descubre estrategias efectivas de reclutamiento digital con métricas clave y casos de éxito.',
                'status' => 'published',
                'is_premium' => false,
                'image_path' => 'posts/reclutamiento-digital.jpg',
                'file_path' => null,
                'author_id' => 1,
                'published_at' => DB::raw('DATE_SUB(NOW(), INTERVAL 25 DAY)'),
                'created_at' => DB::raw('DATE_SUB(NOW(), INTERVAL 25 DAY)'),
                'updated_at' => DB::raw('DATE_SUB(NOW(), INTERVAL 25 DAY)'),
                'tag_categories' => [$rrhhTag->id]
            ],
            [
                'title' => 'Contratos Laborales: Tipos y Consideraciones Legales',
                'slug' => Str::slug('Contratos Laborales: Tipos y Consideraciones Legales'),
                'content' => 'Análisis detallado de los diferentes tipos de contratos laborales, sus implicaciones legales y mejores prácticas para su redacción. Incluye modelos de contratos, cláusulas especiales y consideraciones fiscales.',
                'excerpt' => 'Análisis detallado de tipos de contratos laborales y mejores prácticas para su redacción.',
                'meta_description' => 'Guía completa sobre tipos de contratos laborales, implicaciones legales y modelos de contratos.',
                'tags' => 'contratos, laboral, legal, redacción',
                'category_id' => null,
                'status' => 'published',
                'is_premium' => true,
                'image_path' => 'posts/contratos-laborales.jpg',
                'file_path' => 'posts/modelos-contratos.zip',
                'author_id' => 1,
                'published_at' => now()->subDays(20),
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
                'tag_categories' => [$legalTag->id]
            ],
            [
                'title' => 'Evaluación de Desempeño: Metodologías Modernas',
                'slug' => Str::slug('Evaluación de Desempeño: Metodologías Modernas'),
                'content' => 'Explora las metodologías más efectivas para evaluar el desempeño de tus empleados. Desde sistemas tradicionales hasta enfoques ágiles como OKRs y feedback continuo. Incluye plantillas y herramientas digitales.',
                'excerpt' => 'Metodologías efectivas para evaluar el desempeño con enfoques ágiles y herramientas digitales.',
                'meta_description' => 'Descubre metodologías modernas de evaluación de desempeño con OKRs y feedback continuo.',
                'tags' => 'evaluación, desempeño, OKRs, feedback',
                'category_id' => null,
                'status' => 'published',
                'is_premium' => false,
                'image_path' => 'posts/evaluacion-desempeno.jpg',
                'file_path' => 'posts/plantillas-evaluacion.xlsx',
                'author_id' => 1,
                'published_at' => now()->subDays(15),
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
                'tag_categories' => [$rrhhTag->id]
            ],
            [
                'title' => 'Protección de Datos Personales en el Ámbito Laboral',
                'slug' => Str::slug('Protección de Datos Personales en el Ámbito Laboral'),
                'content' => 'Guía completa sobre el manejo de datos personales de empleados conforme a la normativa vigente. Incluye políticas de privacidad, consentimientos, derechos ARCO y medidas de seguridad.',
                'excerpt' => 'Guía sobre manejo de datos personales de empleados conforme a normativas vigentes.',
                'meta_description' => 'Aprende sobre protección de datos personales laborales, políticas de privacidad y derechos ARCO.',
                'tags' => 'datos personales, privacidad, ARCO, seguridad',
                'category_id' => null,
                'status' => 'draft',
                'is_premium' => true,
                'image_path' => null,
                'file_path' => null,
                'author_id' => 1,
                'published_at' => null,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(5),
                'tag_categories' => [$legalTag->id]
            ],
            [
                'title' => 'Cultura Organizacional: Construcción y Mantenimiento',
                'slug' => Str::slug('Cultura Organizacional: Construcción y Mantenimiento'),
                'content' => 'Aprende a construir y mantener una cultura organizacional sólida que impulse el compromiso y la productividad. Incluye diagnósticos, planes de acción y métricas de seguimiento.',
                'excerpt' => 'Construye y mantén una cultura organizacional sólida con diagnósticos y planes de acción.',
                'meta_description' => 'Guía para construir cultura organizacional sólida con diagnósticos y métricas de seguimiento.',
                'tags' => 'cultura organizacional, compromiso, productividad',
                'category_id' => null,
                'status' => 'published',
                'is_premium' => false,
                'image_path' => 'posts/cultura-organizacional.jpg',
                'file_path' => null,
                'author_id' => 1,
                'published_at' => now()->subDays(8),
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
                'tag_categories' => [$rrhhTag->id]
            ],
            [
                'title' => 'Resolución de Conflictos Laborales',
                'slug' => Str::slug('Resolución de Conflictos Laborales'),
                'content' => 'Estrategias y técnicas para la resolución efectiva de conflictos en el ámbito laboral. Desde la mediación hasta los procedimientos legales, con casos prácticos y recomendaciones.',
                'excerpt' => 'Estrategias y técnicas para resolución efectiva de conflictos laborales con casos prácticos.',
                'meta_description' => 'Aprende estrategias de resolución de conflictos laborales desde mediación hasta procedimientos legales.',
                'tags' => 'conflictos laborales, mediación, resolución',
                'category_id' => null,
                'status' => 'published',
                'is_premium' => true,
                'image_path' => 'posts/conflictos-laborales.jpg',
                'file_path' => 'posts/guia-mediacion.pdf',
                'author_id' => 1,
                'published_at' => now()->subDays(6),
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
                'tag_categories' => [$legalTag->id]
            ],
            [
                'title' => 'Compensaciones y Beneficios: Diseño de Paquetes Atractivos',
                'slug' => Str::slug('Compensaciones y Beneficios: Diseño de Paquetes Atractivos'),
                'content' => 'Diseña paquetes de compensaciones competitivos que atraigan y retengan talento. Incluye análisis de mercado, estructuras salariales y beneficios innovadores.',
                'excerpt' => 'Diseña paquetes de compensaciones competitivos con análisis de mercado y beneficios innovadores.',
                'meta_description' => 'Aprende a diseñar paquetes de compensaciones atractivos con estructuras salariales competitivas.',
                'tags' => 'compensaciones, beneficios, salarios, retención',
                'category_id' => null,
                'status' => 'draft',
                'is_premium' => false,
                'image_path' => null,
                'file_path' => null,
                'author_id' => 1,
                'published_at' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(1),
                'tag_categories' => [$rrhhTag->id]
            ],
            [
                'title' => 'Auditorías Laborales: Preparación y Ejecución',
                'slug' => Str::slug('Auditorías Laborales: Preparación y Ejecución'),
                'content' => 'Guía paso a paso para preparar y ejecutar auditorías laborales efectivas. Incluye checklists, documentación requerida y mejores prácticas para evitar contingencias.',
                'excerpt' => 'Guía paso a paso para auditorías laborales efectivas con checklists y documentación.',
                'meta_description' => 'Aprende a preparar y ejecutar auditorías laborales con checklists y mejores prácticas.',
                'tags' => 'auditorías laborales, checklists, documentación',
                'category_id' => null,
                'status' => 'published',
                'is_premium' => true,
                'image_path' => 'posts/auditorias-laborales.jpg',
                'file_path' => 'posts/checklist-auditoria.pdf',
                'author_id' => 1,
                'published_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
                'tag_categories' => [$legalTag->id]
            ],
            [
                'title' => 'Transformación Digital en RRHH',
                'slug' => Str::slug('Transformación Digital en RRHH'),
                'content' => 'Explora cómo la tecnología está transformando la gestión de recursos humanos. Desde sistemas HRIS hasta inteligencia artificial en reclutamiento y análisis predictivo.',
                'excerpt' => 'Explora la transformación digital en RRHH con sistemas HRIS e inteligencia artificial.',
                'meta_description' => 'Descubre cómo la tecnología transforma RRHH con sistemas HRIS e inteligencia artificial.',
                'tags' => 'transformación digital, HRIS, inteligencia artificial',
                'category_id' => null,
                'status' => 'published',
                'is_premium' => false,
                'image_path' => 'posts/transformacion-digital-rrhh.jpg',
                'file_path' => null,
                'author_id' => 1,
                'published_at' => now()->subDay(),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
                'tag_categories' => [$rrhhTag->id]
            ],
        ];

        foreach ($posts as $postData) {
            // Extraer las categorías de tags antes de crear el post
            $tagCategories = $postData['tag_categories'];
            unset($postData['tag_categories']);
            
            // Remover campos que no existen en la tabla posts
            unset($postData['tags']);
            unset($postData['category_id']);
            
            // Usar DB::table para evitar problemas con timestamps
            $postId = DB::table('posts')->insertGetId([
                'title' => $postData['title'],
                'slug' => $postData['slug'],
                'content' => $postData['content'],
                'excerpt' => $postData['excerpt'],
                'meta_description' => $postData['meta_description'],
                'status' => $postData['status'],
                'is_premium' => $postData['is_premium'],
                'image_path' => $postData['image_path'],
                'file_path' => $postData['file_path'],
                'author_id' => $postData['author_id'],
                'published_at' => isset($postData['published_at']) ? $postData['published_at'] : null,
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()')
            ]);
            
            // Asociar las categorías de tags usando la tabla pivot
            if (!empty($tagCategories)) {
                foreach ($tagCategories as $tagCategoryId) {
                    DB::table('post_tag_category')->insert([
                        'post_id' => $postId,
                        'tag_category_id' => $tagCategoryId,
                        'created_at' => DB::raw('NOW()'),
                        'updated_at' => DB::raw('NOW()')
                    ]);
                }
            }
        }

        $this->command->info('Posts seeder ejecutado correctamente. Se crearon ' . count($posts) . ' publicaciones.');
    }
}