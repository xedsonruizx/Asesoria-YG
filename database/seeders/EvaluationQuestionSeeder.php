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

        $questionsData = [
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
                'options' => json_encode([
                    ['text' => 'Muy actualizado', 'points' => 15],
                    ['text' => 'Actualizado', 'points' => 12],
                    ['text' => 'Poco actualizado', 'points' => 8],
                    ['text' => 'Desactualizado', 'points' => 3]
                ]),
                'points' => 12,
                'show_condition' => json_encode([
                    'parent_order' => 2, // Usar order, no ID
                    'operator' => 'equals',
                    'value' => true
                ]),
                'is_required' => false,
                'order' => 3,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => '¿Por qué no tiene un manual de funciones?',
                'question_type' => 'textarea',
                'placeholder' => 'Explique las razones...',
                'points' => 8,
                'show_condition' => json_encode([
                    'parent_order' => 2,
                    'operator' => 'equals',
                    'value' => false
                ]),
                'is_required' => false,
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
                'options' => json_encode([
                    ['text' => 'Mensual', 'points' => 20],
                    ['text' => 'Trimestral', 'points' => 18],
                    ['text' => 'Semestral', 'points' => 15],
                    ['text' => 'Anual', 'points' => 10]
                ]),
                'points' => 15,
                'show_condition' => json_encode([
                    'parent_order' => 5,
                    'operator' => 'equals',
                    'value' => true
                ]),
                'is_required' => false,
                'order' => 6,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => 'Seleccione las áreas de RRHH que considera importantes (puede marcar varias):',
                'question_type' => 'checkbox',
                'options' => json_encode([
                    ['text' => 'Reclutamiento', 'points' => 5],
                    ['text' => 'Capacitación', 'points' => 6],
                    ['text' => 'Evaluación', 'points' => 4],
                    ['text' => 'Compensación', 'points' => 3],
                    ['text' => 'Bienestar laboral', 'points' => 2]
                ]),
                'points' => 20,
                'is_required' => true,
                'order' => 7,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => '¿Cuál es su nivel de satisfacción con el ambiente laboral?',
                'question_type' => 'radio',
                'options' => json_encode([
                    ['text' => 'Muy satisfecho', 'points' => 25],
                    ['text' => 'Satisfecho', 'points' => 20],
                    ['text' => 'Neutral', 'points' => 15],
                    ['text' => 'Insatisfecho', 'points' => 10],
                    ['text' => 'Muy insatisfecho', 'points' => 5]
                ]),
                'points' => 20,
                'is_required' => true,
                'order' => 8,
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
                'show_condition' => json_encode([
                    'parent_order' => 1,
                    'operator' => 'equals',
                    'value' => true
                ]),
                'is_required' => false,
                'order' => 2,
            ],
            [
                'category_id' => $legalCategory->id,
                'question_text' => '¿Considera que necesita asesoría sobre sus derechos laborales?',
                'question_type' => 'yes_no',
                'points' => 8,
                'show_condition' => json_encode([
                    'parent_order' => 1,
                    'operator' => 'equals',
                    'value' => false
                ]),
                'is_required' => false,
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
                'options' => json_encode([
                    ['text' => 'Despido injustificado', 'points' => 4],
                    ['text' => 'Acoso laboral', 'points' => 3],
                    ['text' => 'Horas extras', 'points' => 2],
                    ['text' => 'Seguridad social', 'points' => 2],
                    ['text' => 'Discriminación', 'points' => 1]
                ]),
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
            [
                'category_id' => $legalCategory->id,
                'question_text' => '¿Qué tipo de contrato laboral tiene?',
                'question_type' => 'radio',
                'options' => json_encode([
                    ['text' => 'Contrato indefinido', 'points' => 20],
                    ['text' => 'Contrato a término fijo', 'points' => 15],
                    ['text' => 'Contrato por obra o labor', 'points' => 12],
                    ['text' => 'Contrato de prestación de servicios', 'points' => 8],
                    ['text' => 'Sin contrato', 'points' => 0]
                ]),
                'points' => 15,
                'is_required' => true,
                'order' => 8,
            ],
            [
                'category_id' => $rrhhCategory->id,
                'question_text' => '¿Tiene conocimiento de sus derechos laborales?',
                'question_type' => 'radio',
                'options' => json_encode([
                    ['text' => 'Sí', 'points' => 15],
                    ['text' => 'No', 'points' => 0]
                ]),
                'points' => 0,
                'is_required' => true,
                'order' => 7,
            ],
        ];

        // 1. Crear todas las preguntas y guardar sus IDs por category y order
        $orderToId = [];
        foreach ($questionsData as $data) {
            // Eliminamos show_condition temporalmente para las dependientes
            $dataToInsert = $data;
            if (isset($dataToInsert['show_condition'])) {
                unset($dataToInsert['show_condition']);
            }
            $question = EvaluationQuestion::create($dataToInsert);
            $orderToId[$data['category_id']][$data['order']] = $question->id;
        }

        // 2. Actualizar show_condition de las dependientes con el ID real
        foreach ($questionsData as $data) {
            if (isset($data['show_condition'])) {
                $condition = json_decode($data['show_condition'], true);
                if (isset($condition['parent_order']) && isset($orderToId[$data['category_id']][$condition['parent_order']])) {
                    $condition['parent_question_id'] = $orderToId[$data['category_id']][$condition['parent_order']];
                    unset($condition['parent_order']);
                    // Actualizar en la base de datos
                    EvaluationQuestion::where('order', $data['order'])
                        ->where('category_id', $data['category_id'])
                        ->update(['show_condition' => json_encode($condition)]);
                }
            }
        }
    }
}