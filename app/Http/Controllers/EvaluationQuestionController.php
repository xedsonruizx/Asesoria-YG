<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationCategory;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Http\Requests\StoreEvaluationQuestionRequest;
use App\Http\Requests\UpdateEvaluationQuestionRequest;

class EvaluationQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EvaluationQuestion::with(['category', 'answers']);

        // Mostrar solo preguntas no eliminadas por defecto
        if (!$request->has('show_deleted')) {
            // Las preguntas eliminadas se excluyen automáticamente con SoftDeletes
        } elseif ($request->show_deleted === 'only') {
            $query->onlyTrashed();
        } elseif ($request->show_deleted === 'with') {
            $query->withTrashed();
        }

        // Aplicar filtros
        if ($request->filled('search')) {
            $query->where('question_text', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('question_type')) {
            $query->where('question_type', $request->question_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Obtener todas las preguntas sin paginación
        $questions = $query->orderBy('order')->get();

        // Agregar información de dependencias a cada pregunta
        $questions = $questions->map(function ($question) {
            // Validar que la pregunta no sea null
            if (!$question) {
                return null;
            }
            
            $question->has_answers = $question->answers()->exists();
            $question->answers_count = $question->answers()->count();
            
            // Asegurar que is_active tenga un valor por defecto
            if (!isset($question->is_active)) {
                $question->is_active = true;
            }
            
            // Asegurar que show_condition sea un array con la nueva estructura
            if ($question->show_condition && is_string($question->show_condition)) {
                $question->show_condition = json_decode($question->show_condition, true);
            }
            
            // Asegurar que options sea un array
            if ($question->options && is_string($question->options)) {
                $question->options = json_decode($question->options, true) ?: [];
            } elseif (!$question->options) {
                $question->options = [];
            }
            
            return $question;
        })->filter(); // Eliminar elementos null

        // Obtener estadísticas
        $stats = [
            'total' => EvaluationQuestion::count(),
            'active' => EvaluationQuestion::where('is_active', true)->count(),
            'inactive' => EvaluationQuestion::where('is_active', false)->count(),
            'with_answers' => EvaluationQuestion::whereHas('answers')->count(),
        ];

        $categories = EvaluationCategory::orderBy('name')->get();

        return Inertia::render('administration/Questions/Index', [
            'questions' => $questions,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'question_type', 'is_active']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = EvaluationCategory::active()->ordered()->get();
        
        // No pasamos preguntas disponibles aquí, se cargarán dinámicamente por categoría
        return Inertia::render('administration/Questions/Create', [
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEvaluationQuestionRequest $request)
    {
        // Tipos de pregunta que tienen puntos individuales por opción
        $typesWithIndividualPoints = ['select', 'radio', 'checkbox'];
        $hasIndividualPoints = in_array($request->question_type, $typesWithIndividualPoints);
        
        $questionData = $request->validated();
        
        // Calcular puntos automáticamente si tiene opciones con puntos
        if ($hasIndividualPoints && !empty($questionData['options'])) {
            $totalPoints = 0;
            
            if ($request->question_type === 'checkbox') {
                // Para checkboxes, sumar todos los puntos
                foreach ($questionData['options'] as $option) {
                    $totalPoints += $option['points'] ?? 0;
                }
            } else {
                // Para select y radio, tomar el máximo
                foreach ($questionData['options'] as $option) {
                    $totalPoints = max($totalPoints, $option['points'] ?? 0);
                }
            }
            
            $questionData['points'] = $totalPoints;
        } elseif (!$hasIndividualPoints && empty($questionData['points'])) {
            // Para tipos sin opciones, establecer puntos por defecto
            $questionData['points'] = 1;
        }
    
        EvaluationQuestion::create($questionData);
    
        // Preservar filtros en la redirección
        $filters = $request->only(['search', 'category_id', 'question_type', 'is_active', 'show_deleted']);
        return redirect()->route('admin.questions.index', $filters)
                       ->with('success', 'Pregunta creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $question = EvaluationQuestion::with(['category', 'answers.evaluation.user'])
            ->findOrFail($id);

        return Inertia::render('administration/Questions/Show', [
            'question' => $question
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $question = EvaluationQuestion::with(['category'])->findOrFail($id);
        $categories = EvaluationCategory::active()->ordered()->get();
        
        // Asegurar que las opciones sean un array
        if ($question->options && is_string($question->options)) {
            $question->options = json_decode($question->options, true) ?: [];
        } elseif (!$question->options) {
            $question->options = [];
        }
        
        // Asegurar que show_condition sea un array con la estructura correcta
        if ($question->show_condition && is_string($question->show_condition)) {
            $question->show_condition = json_decode($question->show_condition, true);
        }
        
        // Si no hay show_condition o está vacío, establecer estructura por defecto
        if (!$question->show_condition) {
            $question->show_condition = [
                'parent_question_id' => null,
                'operator' => 'equals',
                'value' => ''
            ];
        }
        
        // Agregar preguntas disponibles para dependencias (excluyendo la pregunta actual y filtrando por categoría)
        $availableQuestions = EvaluationQuestion::where('id', '!=', $id)
            ->where('category_id', $question->category_id)
            ->orderBy('order')
            ->get(['id', 'question_text', 'order']); // Mantener order para mostrar, pero usar id para dependencias
    
        return Inertia::render('administration/Questions/Edit', [
            'question' => $question,
            'categories' => $categories,
            'availableQuestions' => $availableQuestions
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEvaluationQuestionRequest $request, EvaluationQuestion $question)
    {
        // Tipos de pregunta que tienen puntos individuales por opción
        $typesWithIndividualPoints = ['select', 'radio', 'checkbox'];
        $hasIndividualPoints = in_array($request->question_type, $typesWithIndividualPoints);
        
        $questionData = $request->validated();
        
        // Verificar si el orden ha cambiado
        $orderChanged = $question->order != $request->order;
        $oldOrder = $question->order;
        
        // Si tiene puntos individuales, calcular puntos totales
        if ($hasIndividualPoints) {
            if ($request->has('options') && is_array($request->options)) {
                $optionPoints = array_column($request->options, 'points');
                $optionPoints = array_filter($optionPoints, function($point) {
                    return is_numeric($point);
                });
                
                if (!empty($optionPoints)) {
                    // Para checkbox: suma de todos los puntos
                    // Para select/radio: máximo de los puntos
                    if ($request->question_type === 'checkbox') {
                        $totalPoints = array_sum($optionPoints);
                    } else {
                        $totalPoints = max($optionPoints);
                    }
                    $questionData['points'] = $totalPoints;
                } else {
                    $questionData['points'] = 0;
                }
            } else {
                $questionData['points'] = 0;
            }
        }
    
        $question->update($questionData);
        
        // Si el orden cambió, actualizar las dependencias que referencian esta pregunta
        if ($orderChanged) {
            $this->updateDependentQuestionsOrder($question->id, $question->category_id);
        }
    
        // Preservar filtros en la redirección
        $filters = $request->only(['search', 'category_id', 'question_type', 'is_active', 'show_deleted']);
        return redirect()->route('admin.questions.index', $filters)
                       ->with('success', 'Pregunta actualizada exitosamente.');
    }

    /**
     * Actualizar el orden mostrado en las preguntas dependientes
     * Este método se asegura de que las dependencias muestren el orden correcto
     */
    private function updateDependentQuestionsOrder($questionId, $categoryId)
    {
        // No necesitamos actualizar la base de datos ya que las dependencias
        // se basan en parent_question_id, pero podemos invalidar cache si existe
        // o realizar otras operaciones necesarias para la sincronización
        
        // Log para debugging
        Log::info("Orden actualizado para pregunta ID: {$questionId} en categoría: {$categoryId}");
        
        // Aquí podrías agregar lógica adicional como:
        // - Invalidar cache de preguntas
        // - Notificar a otros servicios
        // - Actualizar índices de búsqueda
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $question = EvaluationQuestion::findOrFail($id);
        
        // Usar soft delete en lugar de eliminación física
        $question->delete();
    
        // Preservar filtros en la redirección
        $filters = request()->only(['search', 'category_id', 'question_type', 'is_active', 'show_deleted']);
        return redirect()->route('admin.questions.index', $filters)
            ->with('success', 'Pregunta eliminada exitosamente. Seguirá apareciendo en reportes existentes.');
    }
    

    /**
     * Restaurar una pregunta eliminada
     */
    public function restore(string $id)
    {
        $question = EvaluationQuestion::withTrashed()->findOrFail($id);
        $question->restore();
    
        // Preservar filtros en la redirección
        $filters = request()->only(['search', 'category_id', 'question_type', 'is_active', 'show_deleted']);
        return redirect()->route('admin.questions.index', $filters)
            ->with('success', 'Pregunta restaurada exitosamente.');
    }

    /**
     * Eliminar permanentemente una pregunta
     */
    public function forceDelete(string $id)
    {
        $question = EvaluationQuestion::withTrashed()->findOrFail($id);
        
        // Verificar si la pregunta tiene respuestas asociadas
        if ($question->answers()->count() > 0) {
            $filters = request()->only(['search', 'category_id', 'question_type', 'is_active', 'show_deleted']);
            return redirect()->route('admin.questions.index', $filters)
                ->with('error', 'No se puede eliminar permanentemente la pregunta porque tiene respuestas asociadas.');
        }
    
        $question->forceDelete();
    
        // Preservar filtros en la redirección
        $filters = request()->only(['search', 'category_id', 'question_type', 'is_active', 'show_deleted']);
        return redirect()->route('admin.questions.index', $filters)
            ->with('success', 'Pregunta eliminada permanentemente.');
    }

    /**
     * Toggle the active status of a question
     */
    public function toggleStatus(string $id)
    {
        $question = EvaluationQuestion::findOrFail($id);
        $question->update(['is_active' => !$question->is_active]);

        $status = $question->is_active ? 'activada' : 'desactivada';
        
        // Preservar filtros en la redirección
        $filters = request()->only(['search', 'category_id', 'question_type', 'is_active', 'show_deleted']);
        return redirect()->route('admin.questions.index', $filters)
            ->with('success', "Pregunta {$status} exitosamente.");
    }

    /**
     * Obtener el próximo número de orden para una categoría
     */
    public function getNextOrder(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:evaluation_categories,id'
        ]);
        
        $nextOrder = EvaluationQuestion::where('category_id', $request->category_id)
                                      ->max('order') + 1;
        
        return response()->json(['next_order' => $nextOrder]);
    }

    /**
     * Get questions by category for dependencies
     */
    public function getQuestionsByCategory(Request $request)
    {
        try {
            $categoryId = $request->get('category_id');
            $excludeId = $request->get('exclude_id');
            
            if (!$categoryId) {
                return response()->json([]);
            }
            
            $query = EvaluationQuestion::where('category_id', $categoryId)
                ->where('is_active', true)
                ->whereNull('deleted_at') // Excluir preguntas eliminadas
                ->orderBy('order'); // Ordenar por order para mostrar
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            // Devolver id, question_text y order - el frontend usará id para dependencias
            $questions = $query->get(['id', 'question_text', 'order']);
            
            return response()->json($questions);
        } catch (Exception $e) {
            Log::warning('Error in getQuestionsByCategory: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading questions'], 500);
        }
    }

}