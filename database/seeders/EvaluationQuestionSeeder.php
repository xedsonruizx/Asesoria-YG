<?php

namespace Database\Seeders;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationCategory;
use Illuminate\Database\Seeder;

class EvaluationQuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener IDs de categorías
        $rrhhCategory = EvaluationCategory::where('slug', 'rrhh')->first();
        $legalCategory = EvaluationCategory::where('slug', 'legal')->first();

        $questions = [
            // Preguntas RRHH
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => '¿Cuántos empleados tiene su empresa?',
                'question_type' => 'number',
                'placeholder' => 'Ingrese el número de empleados',
                'min_value' => 1,
                'max_value' => 10000,
                'points' => 10,
                'is_required' => true,
                'order' => 1,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => '¿Tiene un manual de funciones definido?',
                'question_type' => 'yes_no',
                'points' => 15,
                'is_required' => true,
                'order' => 2,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => '¿Qué tan actualizado está su manual de funciones?',
                'question_type' => 'select',
                'options' => json_encode(['Muy actualizado', 'Actualizado', 'Poco actualizado', 'Desactualizado']),
                'points' => 12,
                'show_condition' => json_encode(['questionId' => 2, 'answer' => true]),
                'is_required' => false, // No required porque es dependiente
                'order' => 3,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => '¿Por qué no tiene un manual de funciones?',
                'question_type' => 'textarea',
                'placeholder' => 'Explique las razones...',
                'points' => 8,
                'show_condition' => json_encode(['questionId' => 2, 'answer' => false]),
                'is_required' => false, // No required porque es dependiente
                'order' => 4,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => '¿Realiza evaluaciones de desempeño?',
                'question_type' => 'yes_no',
                'points' => 18,
                'is_required' => true,
                'order' => 5,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => '¿Con qué frecuencia realiza las evaluaciones?',
                'question_type' => 'select',
                'options' => json_encode(['Mensual', 'Trimestral', 'Semestral', 'Anual']),
                'points' => 15,
                'show_condition' => json_encode(['questionId' => 5, 'answer' => true]),
                'is_required' => false, // No required porque es dependiente
                'order' => 6,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => 'Seleccione las áreas de RRHH que considera importantes (puede marcar varias):',
                'question_type' => 'checkbox',
                'options' => json_encode(['Reclutamiento', 'Capacitación', 'Evaluación', 'Compensación', 'Bienestar laboral']),
                'points' => 20,
                'is_required' => true,
                'order' => 7,
            ],
            
            // Preguntas LEGAL
            [
                'category_id' => $legalCategory->id,
                'question_text' => '¿Conoce sus derechos laborales básicos?',
                'question_type' => 'yes_no',
                'points' => 15,
                'is_required' => true,
                'order' => 1,
            ],
            [
                'category_id' => $legalCategory->id,
                'question_text' => '¿Ha tenido que hacer valer sus derechos ante su empleador?',
                'question_type' => 'yes_no',
                'points' => 10,
                'show_condition' => json_encode(['questionId' => 8, 'answer' => true]),
                'is_required' => false, // No required porque es dependiente
                'order' => 2,
            ],
            [
                'category_id' => $legalCategory->id,
                'question_text' => '¿Considera que necesita asesoría sobre sus derechos laborales?',
                'question_type' => 'yes_no',
                'points' => 8,
                'show_condition' => json_encode(['questionId' => 8, 'answer' => false]),
                'is_required' => false, // No required porque es dependiente
                'order' => 3,
            ],
            [
                'category_id' => $legalCategory->id,
                'question_text' => '¿Obtuvo una respuesta satisfactoria de su empleador?',
                'question_type' => 'yes_no',
                'points' => 18,
                'is_required' => true,
                'order' => 4,
            ],
            [
                'category_id' => $legalCategory->id,
                'question_text' => 'Seleccione los temas legales que le interesan (puede marcar varios):',
                'question_type' => 'checkbox',
                'points' => 12,
                'options' => json_encode(['Despido injustificado', 'Acoso laboral', 'Horas extras', 'Seguridad social', 'Discriminación']),
                'is_required' => true,
                'order' => 5,
            ],
            [
                'category_id' => $legalCategory->id,
                'question_text' => '¿Está afiliado al seguro social?',
                'question_type' => 'yes_no',
                'points' => 20,
                'is_required' => true,
                'order' => 6,
            ],
            [
                'category_id' => $legalCategory->id,
                'question_text' => '¿Su empleador cumple con las normativas laborales básicas?',
                'question_type' => 'yes_no',
                'points' => 15,
                'is_required' => true,
                'order' => 7,
            ],
        ];

        foreach ($questions as $question) {
            EvaluationQuestion::create($question);
        }
    }
}