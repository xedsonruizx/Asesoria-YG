<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationCategory;
use Illuminate\Support\Facades\Log;
use Exception;

class EvaluationQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EvaluationQuestion::with(['category', 'answers']);

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
            $question->has_answers = $question->answers()->exists();
            $question->answers_count = $question->answers()->count();
            
            // Asegurar que show_condition sea un array con la nueva estructura
            if ($question->show_condition && is_string($question->show_condition)) {
                $question->show_condition = json_decode($question->show_condition, true);
            }
            
            // Asegurar que options sea un array
            if ($question->options && is_string($question->options)) {
                $question->options = json_decode($question->options, true);
            }
            
            // Agregar información de la pregunta padre si existe
            if ($question->show_condition && isset($question->show_condition['parent_question_id'])) {
                $parentQuestion = EvaluationQuestion::find($question->show_condition['parent_question_id']);
                $question->parent_question = $parentQuestion ? $parentQuestion->question_text : null;
            }
            
            return $question;
        });

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
    public function store(Request $request)
    {
        // Tipos de pregunta que tienen puntos individuales por opción
        $typesWithIndividualPoints = ['select', 'radio', 'checkbox'];
        $hasIndividualPoints = in_array($request->question_type, $typesWithIndividualPoints);
        
        $request->validate([
            'category_id' => 'required|exists:evaluation_categories,id',
            'question_text' => 'required|string|max:500',
            'question_type' => 'required|in:text,textarea,select,number,checkbox,yes_no,radio',
            'options' => 'nullable|array',
            'placeholder' => 'nullable|string|max:255',
            'min_value' => 'nullable|integer',
            'max_value' => 'nullable|integer|gte:min_value',
            // Solo requerir puntos si no tiene puntos individuales
            'points' => $hasIndividualPoints ? 'nullable|integer|min:0' : 'required|integer|min:1',
            'show_condition' => 'nullable|array',
            'validation_rules' => 'nullable|array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ]);
    
        // Si tiene puntos individuales, establecer puntos base en 0
        $questionData = $request->all();
        if ($hasIndividualPoints) {
            $questionData['points'] = 0;
        }
    
        // Auto-generar el orden basado en la categoría
        $nextOrder = EvaluationQuestion::where('category_id', $request->category_id)
                                      ->max('order') + 1;
        
        $questionData = $request->all();
        $questionData['order'] = $nextOrder;
    
        EvaluationQuestion::create($questionData);
    
        return redirect()->route('admin.questions.index')
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
        
        // Agregar preguntas disponibles para dependencias (excluyendo la pregunta actual y filtrando por categoría)
        $availableQuestions = EvaluationQuestion::where('id', '!=', $id)
            ->where('category_id', $question->category_id)
            ->orderBy('order')
            ->get(['id', 'question_text']);
    
        return Inertia::render('administration/Questions/Edit', [
            'question' => $question,
            'categories' => $categories,
            'availableQuestions' => $availableQuestions
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EvaluationQuestion $question)
    {
        // Tipos de pregunta que tienen puntos individuales por opción
        $typesWithIndividualPoints = ['select', 'radio', 'checkbox'];
        $hasIndividualPoints = in_array($request->question_type, $typesWithIndividualPoints);
        
        $request->validate([
            'category_id' => 'required|exists:evaluation_categories,id',
            'question_text' => 'required|string|max:500',
            'question_type' => 'required|in:text,textarea,select,number,checkbox,yes_no,radio',
            'options' => 'nullable|array',
            'placeholder' => 'nullable|string|max:255',
            'min_value' => 'nullable|integer',
            'max_value' => 'nullable|integer|gte:min_value',
            // Solo requerir puntos si no tiene puntos individuales
            'points' => $hasIndividualPoints ? 'nullable|integer|min:0' : 'required|integer|min:1',
            'show_condition' => 'nullable|array',
            'validation_rules' => 'nullable|array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ]);
    
        // Si tiene puntos individuales, establecer puntos base en 0
        $questionData = $request->all();
        if ($hasIndividualPoints) {
            $questionData['points'] = 0;
        }
    
        // Si cambió la categoría, recalcular el orden
        if ($request->category_id != $question->category_id) {
            $nextOrder = EvaluationQuestion::where('category_id', $request->category_id)
                                          ->max('order') + 1;
            $questionData['order'] = $nextOrder;
        } else {
            // Mantener el orden actual si no cambió la categoría
            $questionData['order'] = $question->order;
        }
    
        $question->update($questionData);
    
        return redirect()->route('admin.questions.index')
                       ->with('success', 'Pregunta actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $question = EvaluationQuestion::findOrFail($id);
        
        // Verificar si la pregunta tiene respuestas asociadas
        if ($question->answers()->count() > 0) {
            return redirect()->route('admin.questions.index')
                ->with('error', 'No se puede eliminar la pregunta porque tiene respuestas asociadas.');
        }

        $question->delete();

        return redirect()->route('admin.questions.index')
            ->with('success', 'Pregunta eliminada exitosamente.');
    }

    /**
     * Toggle the active status of a question
     */
    public function toggleStatus(string $id)
    {
        $question = EvaluationQuestion::findOrFail($id);
        $question->update(['is_active' => !$question->is_active]);

        $status = $question->is_active ? 'activada' : 'desactivada';
        return redirect()->route('admin.questions.index')
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
                ->orderBy('order');
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            $questions = $query->get(['id', 'question_text']);
            
            return response()->json($questions);
        } catch (Exception $e) {
            Log::warning('Error in getQuestionsByCategory: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading questions'], 500);
        }
    }

}