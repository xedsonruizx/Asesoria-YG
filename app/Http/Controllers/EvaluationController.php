<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationCategory;
use App\Models\EvaluationQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\ErrorHandler\Debug;

class EvaluationController extends Controller
{
    public function index()
    {
        // INDEX DONDE SE EVALUA AL USUARIO Y SE MUESTRA EL RESULTADO DE LA EVALUACION
        
        $user = Auth::user();
        $evaluation = $user->evaluations()->latest()->first();
        
        if (!$evaluation) {
            $evaluation = Evaluation::create([
                'user_id' => $user->id,
                'category_scores' => [],
                'category_progress' => [],
                'total_score' => 0,
                'total_progress' => 0,
                'status' => 'draft',
                'is_active' => true,
            ]);
        }
    
        // Obtener solo respuestas existentes (preguntas con respuesta)
        $existingAnswers = [];
        $answeredQuestionIds = [];
        if ($evaluation->answers()->exists()) {
            foreach ($evaluation->answers as $answer) {
                $existingAnswers[$answer->question_id] = $answer->answer_value;
                $answeredQuestionIds[] = $answer->question_id;
            }
        }
    
        // Si la evaluación ya está completada, mostrar resultados
        if ($evaluation->status === 'completed') {
            $report = $evaluation->generateReport();
            
            // Convertir el formato del backend al formato esperado por el frontend
            $categoryScores = [];
            foreach ($report['categories'] as $category) {
                $categoryScores[$category['name']] = [
                    'category' => $category['name'],
                    'score' => $category['score'],
                    'maxScore' => $category['total_possible_points'],
                    'progress' => $category['progress'],
                    'percentage' => $category['percentage'],
                    'obtainedPoints' => $category['obtained_points'],
                    'totalPossiblePoints' => $category['total_possible_points'],
                    'answeredQuestions' => $category['answered_questions']
                ];
            }
            
            // Obtener solo preguntas que tienen respuestas
            $questionsWithAnswers = EvaluationQuestion::whereIn('id', $answeredQuestionIds)
                ->where('is_active', true)
                ->orderBy('order')
                ->get();
            
            $categories = EvaluationCategory::where('is_active', true)
                ->withCount(['questions' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->having('questions_count', '>', 0)
                ->orderBy('id')
                ->get();
            
            // Asegurar que las multas activadas estén disponibles
            $triggeredMultas = $evaluation->triggered_multas ?? [];
            
            // Si no hay multas guardadas pero hay respuestas, re-evaluar
            if (empty($triggeredMultas) && !empty($answeredQuestionIds)) {
                $triggeredMultas = $evaluation->evaluateTriggeredMultas();
            }
            
            $finalData = [
                'evaluation' => $evaluation,
                'report' => $report,
                'showResults' => true,
                'categoryScores' => $categoryScores,
                'answers' => $existingAnswers,
                'categories' => $categories,
                'questions' => $questionsWithAnswers,
                'triggeredMultas' => $triggeredMultas,
            ];
            
            return Inertia::render('ClientMenu/Evaluation', $finalData);
        }
    
        // Para evaluaciones en progreso
        $categories = EvaluationCategory::where('is_active', true)
            ->withCount(['questions' => function ($query) {
                $query->where('is_active', true);
            }])
            ->having('questions_count', '>', 0)
            ->orderBy('id')
            ->get();
        $questions = EvaluationQuestion::where('is_active', true)->orderBy('order')->get();
    
        $finalData = [
            'evaluation' => $evaluation,
            'categories' => $categories,
            'questions' => $questions,
            'answers' => $existingAnswers,
            'showResults' => false,
            'categoryScores' => []
        ];
    
        return Inertia::render('ClientMenu/Evaluation', $finalData);
    }

    public function report(Evaluation $evaluation)
    {
        if ($evaluation->user_id !== Auth::id()) {
            abort(403);
        }

        $report = $evaluation->generateReport();

        return Inertia::render('ClientMenu/EvaluationReport', [
            'evaluation' => $evaluation,
            'report' => $report,
        ]);
    }

    public function exportReport(Evaluation $evaluation)
    {
        if ($evaluation->user_id !== Auth::id()) {
            abort(403);
        }

        $report = $evaluation->generateReport();
        
        // Aquí puedes implementar la exportación a PDF, Excel, etc.
        return response()->json($report);
    }

   public function saveAnswer(Request $request)
{
    $request->validate([
        'evaluation_id' => 'required|integer',
        'question_id' => 'required|integer',
        'answer_value' => 'nullable',
    ]);

    $question = EvaluationQuestion::findOrFail($request->question_id);
    $answerValue = $request->answer_value;

    Log::info('saveAnswer', [
        'evaluation_id' => $request->evaluation_id,
        'question_id' => $request->question_id,
        'answer_value' => $answerValue,
    ]);

    Log::info('saveAnswer', [
        'evaluation_id' => $question->evaluation_id,
        'question_id' => $question->id,
        'answer_value' => $answerValue,
    ]);

    // Si el valor está vacío, eliminar la respuesta
    if (empty($answerValue) || (is_array($answerValue) && empty(array_filter($answerValue)))) {
        EvaluationAnswer::where([
            'evaluation_id' => $request->evaluation_id,
            'question_id' => $request->question_id,
        ])->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Respuesta eliminada',
            'deleted' => true
        ]);
    }
    
    Log::info('PASO 1');
    // Para preguntas de checkbox, asegurar que sea un array
    if ($question->question_type === 'checkbox' && !is_array($answerValue)) {
        $answerValue = [$answerValue];
    }
    
    // Para preguntas yes_no, convertir a boolean
    if ($question->question_type === 'yes_no') {
        $answerValue = filter_var($answerValue, FILTER_VALIDATE_BOOLEAN);
    }

    // 🔑 Normalizar antes de guardar
    if (is_array($answerValue)) {
        $answerValue = json_encode($answerValue);
    } elseif (is_bool($answerValue)) {
        $answerValue = $answerValue ? '1' : '0';
    }
    Log::info('PASO 2');
    try {
        $answer = EvaluationAnswer::where([
            'evaluation_id' => $request->evaluation_id,
            'question_id'   => $request->question_id,
        ])->first();

        if ($answer) {
            $answer->update(['answer_value' => $answerValue]);
        } else {
            $answer = EvaluationAnswer::create([
                'evaluation_id' => $request->evaluation_id,
                'question_id'   => $request->question_id,
                'answer_value'  => $answerValue,
            ]);
        }
        Log::info('PASO 3');
        return response()->json([
            'success' => true,
            'answer' => $answer
        ]);
    } catch (\Exception $e) {
        Log::error('Error en saveAnswer: ' . $e->getMessage(), [
            'evaluation_id' => $request->evaluation_id,
            'question_id' => $request->question_id,
            'answer_value' => $answerValue,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error al guardar la respuesta. Intenta nuevamente.'
        ], 500);
    }
}




    public function submit(Request $request)
    {
        $user = Auth::user();
        $evaluation = $user->evaluations()->latest()->first();
        
        if (!$evaluation) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una evaluación activa'
            ], 404);
        }
    
        try {
            DB::beginTransaction();
            
            // 🔑 BORRAR FÍSICAMENTE todas las respuestas existentes (no soft delete)
            $deletedCount = $evaluation->answers()->forceDelete(); // Eliminar físicamente
            
            // Guardar todas las respuestas nuevas
            if ($request->has('answers')) {
                foreach ($request->answers as $questionId => $answerValue) {
                    // Validar que questionId sea válido y answerValue no sea null/vacío
                    if ($questionId > 0 && $answerValue !== null && $answerValue !== '') {
                        
                        // Normalizar el valor antes de guardar
                        $normalizedValue = is_array($answerValue) ? json_encode($answerValue) : $answerValue;
                        
                        // Crear nueva respuesta
                        $answer = EvaluationAnswer::create([
                            'evaluation_id' => $evaluation->id,
                            'question_id'   => $questionId,
                            'answer_value'  => $normalizedValue,
                            'status'        => 'completed',
                        ]);
    
                        // Calcular puntos usando el método correcto
                        $answer->calculatePointsSafely();
                        
                    }
                }
            }
    
            // Calcular scores por categoría
            $evaluation->calculateScoresByCategory();
            
            // Evaluar multas activadas basándose en las respuestas
            $triggeredMultas = $evaluation->evaluateTriggeredMultas();
            
            // Marcar evaluación como completada
            $evaluation->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
    
            DB::commit();
            
            
            return response()->json([
                'success' => true,
                'message' => 'Evaluación completada exitosamente',
                'evaluation' => $evaluation->fresh(),
                'triggered_multas' => $triggeredMultas, // Incluir multas activadas en la respuesta
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al completar evaluación: ' . $e->getMessage(), [
                'evaluation_id' => $evaluation->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al completar la evaluación. Intenta nuevamente.'
            ], 500);
        }
    }

    public function restart(Request $request)
    {
        $user = Auth::user();
        $evaluation = $user->evaluations()->latest()->first();
        
        if (!$evaluation) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una evaluación activa'
            ], 404);
        }
    
        // Eliminar todas las respuestas
        $evaluation->answers()->delete();
    
        // Resetear scores y progreso
        $evaluation->update([
            'category_scores' => [],
            'category_progress' => [],
            'total_score' => 0,
            'total_progress' => 0,
            'status' => 'draft',
            'completed_at' => null,
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Evaluación reiniciada exitosamente',
            'evaluation' => $evaluation->fresh(),
        ]);
    }

    public function exportHistory()
    {
        $user = Auth::user();
        $evaluations = $user->evaluations()
            ->where('status', 'completed')
            ->with('answers.question')
            ->get();
        
        // Generar PDF o Excel con historial completo
        return response()->download($filePath);
    }

    public function history()
    {
        $user = Auth::user();
        $evaluations = $user->evaluations()
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get();
        
        return Inertia::render('ClientMenu/EvaluationHistory', [
            'evaluations' => $evaluations
        ]);
    }
}