<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationCategory;
use App\Models\EvaluationQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EvaluationController extends Controller
{
    public function index()
    {
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
            ]);
        }
    
        // Si la evaluación ya está completada, mostrar resultados
        if ($evaluation->status === 'completed') {
            $report = $evaluation->generateReport();
            
            // Agregar el campo is_completed para compatibilidad con el frontend
            $evaluationData = $evaluation->toArray();
            $evaluationData['is_completed'] = true;
            
            // Preparar los datos de categorías para el frontend
            $categoryScores = [];
            foreach ($report['categories'] as $category) {
                $categoryScores[$category['name']] = [
                    'category' => $category['name'],
                    'score' => $category['score'],
                    'maxScore' => $category['max_score'],
                    'progress' => $category['percentage']
                ];
            }
            
            return Inertia::render('ClientMenu/Evaluation', [
                'evaluation' => $evaluationData,
                'report' => $report,
                'showResults' => true,
                'categoryScores' => $categoryScores, // Pasar los puntajes calculados
                'categories' => [],
                'questions' => [],
                'answers' => [],
            ]);
        }
    
        $categories = EvaluationCategory::active()->ordered()->with('questions')->get();
        $questions = EvaluationQuestion::active()->ordered()->get()->map(function ($question) {
            // Asegurar que las opciones sean un array
            if ($question->options && is_string($question->options)) {
                $decodedOptions = json_decode($question->options, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $question->options = $decodedOptions;
                } else {
                    // Si falla el JSON decode, mantener como string para manejo en frontend
                    error_log("Failed to decode options for question {$question->id}: " . json_last_error_msg());
                }
            }
            // Asegurar que show_condition sea un array
            if ($question->show_condition && is_string($question->show_condition)) {
                $question->show_condition = json_decode($question->show_condition, true);
            }
            return $question;
        });
        $answers = $evaluation->answers()->with('question')->get()
            ->keyBy('question_id')
            ->map(fn($answer) => $answer->answer_value);
    
        return Inertia::render('ClientMenu/Evaluation', [
            'evaluation' => $evaluation,
            'categories' => $categories,
            'questions' => $questions,
            'answers' => $answers,
            'showResults' => false,
        ]);
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
            'evaluation_id' => 'required|exists:evaluations,id',
            'question_id' => 'required|exists:evaluation_questions,id',
            'answer_value' => 'required',
        ]);
    
        $evaluation = Evaluation::findOrFail($request->evaluation_id);
        
        // Verificar que la evaluación pertenece al usuario autenticado
        if ($evaluation->user_id !== Auth::id()) {
            abort(403);
        }
    
        // Obtener la pregunta para validar el tipo de respuesta
        $question = EvaluationQuestion::findOrFail($request->question_id);
        
        // Procesar la respuesta según el tipo de pregunta
        $answerValue = $request->answer_value;
        
        // Para preguntas de checkbox, asegurar que sea un array
        if ($question->question_type === 'checkbox' && !is_array($answerValue)) {
            $answerValue = [$answerValue];
        }
        
        // Para preguntas yes_no, convertir a boolean
        if ($question->question_type === 'yes_no') {
            $answerValue = filter_var($answerValue, FILTER_VALIDATE_BOOLEAN);
        }
    
        $answer = EvaluationAnswer::updateOrCreate(
            [
                'evaluation_id' => $request->evaluation_id,
                'question_id' => $request->question_id,
            ],
            ['answer_value' => $answerValue]
        );
    
        // Calcular puntos y actualizar scores usando el método correcto
        $answer->calculatePoints();
        $evaluation->calculateScoresByCategory();
    
        return response()->json([
            'success' => true,
            'evaluation' => $evaluation->fresh(),
            'points_earned' => $answer->points_earned,
        ]);
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
    
        // Guardar todas las respuestas
        if ($request->has('answers')) {
            foreach ($request->answers as $questionId => $answerValue) {
                // Validar que questionId sea válido y answerValue no sea null
                if ($questionId > 0 && $answerValue !== null && $answerValue !== '') {
                    $answer = EvaluationAnswer::updateOrCreate(
                        [
                            'evaluation_id' => $evaluation->id,
                            'question_id' => $questionId,
                        ],
                        ['answer_value' => $answerValue]
                    );
                    
                    // Calcular puntos para cada respuesta
                    $answer->calculatePoints();
                }
            }
        }
    
        // Usar el método correcto y eliminar campos inexistentes
        $evaluation->calculateScoresByCategory();
        
        $evaluation->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Evaluación completada exitosamente',
            'evaluation' => $evaluation->fresh(),
        ]);
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