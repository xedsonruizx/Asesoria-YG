<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluationQuestionRequest;
use App\Http\Requests\UpdateEvaluationQuestionRequest;
use App\Models\EvaluationCategory;
use App\Models\EvaluationQuestion;
use App\Models\Multa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Exception;

class EvaluationQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EvaluationQuestion::with(['category', 'multas'])
            ->withCount('answers')
            ->orderBy('order');

        // Aplicar filtros existentes...
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
            $query->where('is_active', $request->is_active === 'true');
        }

        if ($request->boolean('show_deleted')) {
            $query->withTrashed();
        }

        $questions = $query->get(); // Cambiar de paginate() a get()
        $categories = EvaluationCategory::active()->get();
        $multas = Multa::active()->get();

        return Inertia::render('administration/Questions/Index', [
            'questions' => $questions,
            'categories' => $categories,
            'multas' => $multas,
            'filters' => $request->only(['search', 'category_id', 'question_type', 'is_active', 'show_deleted'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = EvaluationCategory::active()->get();
        $multas = Multa::active()->get(['id', 'name', 'description']);
        
        return Inertia::render('administration/Questions/Create', [
            'categories' => $categories,
            'multas' => $multas
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
    
        // Usar transacción para asegurar consistencia
        $question = DB::transaction(function () use ($questionData, $request) {
            // Crear la pregunta
            $question = EvaluationQuestion::create($questionData);
            
            // Asociar multas si están presentes
            if ($request->has('multa_condition') && !empty($request->multa_condition)) {
                $multaCondition = $request->multa_condition;
                
                // Verificar que multa_id no esté vacío y sea un número válido
                if (!empty($multaCondition['multa_id']) && is_numeric($multaCondition['multa_id'])) {
                    $question->multas()->attach($multaCondition['multa_id'], [
                        'trigger_condition' => $multaCondition['trigger_condition'] ?? 'always',
                        'trigger_value' => $multaCondition['trigger_value'] ?? null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            
            return $question;
        });
    
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
    public function edit(EvaluationQuestion $question)
    {
        $question->load(['category', 'multas']);
        $categories = EvaluationCategory::active()->get();
        $multas = Multa::active()->get(['id', 'name', 'description']);
        
        // Transformar los datos de multas para el frontend
        $multaCondition = null;
        if ($question->multas->isNotEmpty()) {
            $firstMulta = $question->multas->first();
            $multaCondition = [
                'multa_id' => $firstMulta->id,
                'trigger_condition' => $firstMulta->pivot->trigger_condition,
                'trigger_value' => $firstMulta->pivot->trigger_value
            ];
        }
        
        // Agregar multa_condition al objeto question
        $question->multa_condition = $multaCondition;
        
        return Inertia::render('administration/Questions/Edit', [
            'question' => $question,
            'categories' => $categories,
            'multas' => $multas
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
    
        // Usar transacción para asegurar consistencia
        DB::transaction(function () use ($question, $questionData, $request) {
            // Actualizar la pregunta
            $question->update($questionData);
            
            // ELIMINAR COMPLETAMENTE todas las asociaciones anteriores de multas
            $question->multas()->detach();
            
            // Sincronizar multas - asociar nuevas multas si están presentes
            if ($request->has('multa_condition')) {
                $multaCondition = $request->multa_condition;
                
                // Asociar la nueva multa si está presente y es válida
                if (!empty($multaCondition['multa_id']) && is_numeric($multaCondition['multa_id'])) {
                    $question->multas()->attach($multaCondition['multa_id'], [
                        'trigger_condition' => $multaCondition['trigger_condition'] ?? 'always',
                        'trigger_value' => $multaCondition['trigger_value'] ?? null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            
            // Si hay respuestas existentes para esta pregunta y se cambió el tipo o las opciones,
            // invalidar las respuestas que ya no son válidas
            if ($question->wasChanged(['question_type', 'options'])) {
                $this->invalidateIncompatibleAnswers($question);
            }
        });
        
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
     * Invalidar respuestas que ya no son compatibles con la pregunta actualizada
     */
    private function invalidateIncompatibleAnswers(EvaluationQuestion $question)
    {
        // Si cambió el tipo de pregunta o las opciones, las respuestas existentes
        // podrían no ser válidas, así que las marcamos para revisión
        $question->answers()->update([
            'is_active' => false,
            'updated_at' => now()
        ]);
        
        Log::info("Respuestas invalidadas para pregunta ID: {$question->id} debido a cambios en tipo o opciones");
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
        
        // Eliminar en cascada todas las relaciones asociadas
        
        // 1. Eliminar todas las respuestas de evaluación asociadas
        $question->answers()->forceDelete();
        
        // 2. Desasociar todas las multas relacionadas
        $question->multas()->detach();
        
        // 3. Actualizar preguntas dependientes que referencian esta pregunta
        // Buscar preguntas que tienen esta pregunta como dependencia
        EvaluationQuestion::where('show_condition->parent_question_id', $id)
            ->update([
                'show_condition' => null
            ]);
        
        // 4. Finalmente, eliminar la pregunta permanentemente
        $question->forceDelete();
    
        // Preservar filtros en la redirección
        $filters = request()->only(['search', 'category_id', 'question_type', 'is_active', 'show_deleted']);
        return redirect()->route('admin.questions.index', $filters)
            ->with('success', 'Pregunta y todas sus relaciones eliminadas permanentemente.');
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