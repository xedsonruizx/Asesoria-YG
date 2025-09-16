<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Evaluation;
use App\Models\User;
use App\Models\EvaluationCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\Log;

class EvaluationAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Evaluation::with(['user', 'answers.question.category'])
                          ->where('status', 'completed') // Solo evaluaciones completadas
                          ->has('answers'); // Solo evaluaciones que tengan al menos 1 respuesta
        
        // Filtrar evaluaciones que tengan respuestas en categorías activas
        $query->whereHas('answers.question.category', function ($q) {
            $q->where('is_active', true);
        });
        
        // Filtro para mostrar eliminados
        if ($request->filled('show_deleted')) {
            if ($request->show_deleted === 'only') {
                $query->onlyTrashed();
            } elseif ($request->show_deleted === 'with') {
                $query->withTrashed();
            }
        }
        
        // Filtro por estado activo/inactivo
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Filtro por usuario
        if ($request->filled('user')) {
            $userFilter = $request->user;
            $query->whereHas('user', function ($q) use ($userFilter) {
                $q->where('name', 'like', '%' . $userFilter . '%')
                  ->orWhere('email', 'like', '%' . $userFilter . '%');
            });
        }
        
        $evaluations = $query->latest()->paginate(10);
    
        // Generar puntajes por categoría para cada evaluación
        $evaluationsWithScores = $evaluations->getCollection()->map(function ($evaluation) {
            // Solo procesar si la evaluación tiene respuestas en categorías activas
            $activeAnswers = $evaluation->answers->filter(function ($answer) {
                return $answer->question && 
                       $answer->question->category && 
                       $answer->question->category->is_active;
            });
            
            if ($activeAnswers->count() > 0) {
                // Guardar el status original para evitar que se cambie automáticamente
                $originalStatus = $evaluation->status;
                $originalCompletedAt = $evaluation->completed_at;
                
                // Asegurar que los puntajes estén actualizados
                $evaluation->calculateScoresByCategory();
                
                // Restaurar el status original si era completada
                if ($originalStatus === 'completed') {
                    $evaluation->update([
                        'status' => 'completed',
                        'completed_at' => $originalCompletedAt
                    ]);
                }
                
                // Generar reporte completo con detalles por categoría
                $report = $evaluation->generateReport();
                
                // Filtrar solo categorías activas en el reporte
                $activeCategoryDetails = collect($report['categories'])->filter(function ($category) {
                    // Verificar si la categoría está activa
                    $categoryModel = \App\Models\EvaluationCategory::where('slug', $category['slug'])->first();
                    return $categoryModel && $categoryModel->is_active;
                })->values()->toArray();
                
                // Agregar los datos del reporte a la evaluación
                $evaluation->category_details = $activeCategoryDetails;
                $evaluation->total_percentage = $report['total_percentage'] ?? 0;
            } else {
                // Si no hay respuestas en categorías activas, establecer valores por defecto
                $evaluation->category_details = [];
                $evaluation->total_percentage = 0;
            }
            
            return $evaluation;
        });
        
        // Reemplazar la colección en el paginador
        $evaluations->setCollection($evaluationsWithScores);
    
        // Actualizar las estadísticas para reflejar solo evaluaciones con respuestas en categorías activas
        $stats = [
            'total' => Evaluation::where('status', 'completed')
                                ->has('answers')
                                ->whereHas('answers.question.category', fn($q) => $q->where('is_active', true))
                                ->count(),
            'completed' => Evaluation::where('status', 'completed')
                                    ->has('answers')
                                    ->whereHas('answers.question.category', fn($q) => $q->where('is_active', true))
                                    ->count(),
            'in_progress' => Evaluation::where('status', 'in_progress')->count(),
            'draft' => Evaluation::where('status', 'draft')->count(),
            'active' => Evaluation::where('status', 'completed')
                                 ->where('is_active', true)
                                 ->has('answers')
                                 ->whereHas('answers.question.category', fn($q) => $q->where('is_active', true))
                                 ->count(),
            'inactive' => Evaluation::where('status', 'completed')
                                   ->where('is_active', false)
                                   ->has('answers')
                                   ->whereHas('answers.question.category', fn($q) => $q->where('is_active', true))
                                   ->count(),
        ];
    
        $categories = EvaluationCategory::active()->ordered()->get();
    
        return Inertia::render('administration/Evaluation/Index', [
            'evaluations' => $evaluations,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => $request->only(['user', 'show_deleted', 'is_active'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::all(['id', 'name', 'email']);
        $categories = EvaluationCategory::active()->ordered()->get();
        
        return Inertia::render('administration/Evaluation/Create', [
            'users' => $users,
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,in_progress,completed',
        ]);

        $evaluation = Evaluation::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
            'category_scores' => [],
            'category_progress' => [],
            'total_score' => 0,
            'total_progress' => 0,
        ]);

        return redirect()->route('admin.evaluations.index')
            ->with('success', 'Evaluación creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $evaluation = Evaluation::with(['user', 'answers.question'])
            ->findOrFail($id);

        $report = $evaluation->generateReport();

        return Inertia::render('administration/Evaluation/Show', [
            'evaluation' => $evaluation,
            'report' => $report
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $evaluation = Evaluation::with(['user'])->findOrFail($id);

        $categories = EvaluationCategory::active()->ordered()->get();

        return Inertia::render('administration/Evaluation/Edit', [
            'evaluation' => $evaluation,
            'categories' => $categories
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $evaluation = Evaluation::findOrFail($id);
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,in_progress,completed',
        ]);

        $evaluation->update([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.evaluations.index')
            ->with('success', 'Evaluación actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $evaluation = Evaluation::findOrFail($id);
            $evaluation->forceDelete(); // Hard delete definitivo

            return redirect()->route('admin.evaluations.index')
                           ->with('success', 'Evaluación eliminada definitivamente.');
        } catch (Exception $e) {
            Log::error('Error al eliminar evaluación: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al eliminar la evaluación.']);
        }
    }

    /**
     * Restore a soft deleted evaluation
     */
    public function restore($id)
    {
        try {
            $evaluation = Evaluation::onlyTrashed()->findOrFail($id);
            $evaluation->restore();

            return redirect()->route('admin.evaluations.index')
                           ->with('success', 'Evaluación restaurada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al restaurar evaluación: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al restaurar la evaluación.']);
        }
    }

    /**
     * Toggle status of evaluation
     */
    public function toggleStatus($id)
    {
        try {
            $evaluation = Evaluation::findOrFail($id);
            $evaluation->update([
                'is_active' => !$evaluation->is_active
            ]);

            $status = $evaluation->is_active ? 'activada' : 'desactivada';
            return redirect()->route('admin.evaluations.index')
                           ->with('success', "Evaluación {$status} exitosamente.");
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de evaluación: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al cambiar el estado de la evaluación.']);
        }
    }

    /**
     * Reset evaluation answers and progress
     */
    public function reset(string $id)
    {
        $evaluation = Evaluation::findOrFail($id);
        
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

        return redirect()->route('admin.evaluations.show', $id)
            ->with('success', 'Evaluación reiniciada exitosamente.');
    }

    /**
     * Generate PDF report for evaluation
     */
    public function generatePdf(string $id)
    {
        $evaluation = Evaluation::with(['user', 'answers.question.category'])
            ->findOrFail($id);
    
        if ($evaluation->status !== 'completed') {
            return redirect()->back()->with('error', 'Solo se pueden generar PDFs de evaluaciones completadas.');
        }
    
        $report = $evaluation->generateReport();
        $companyName = config('app.company_name', 'Asesorías YG');
        
        // Agrupar preguntas y respuestas por categoría con puntos obtenidos
        // Cambiar la lógica para usar las respuestas directamente de la evaluación
        $questionsByCategory = [];
        
        // Obtener todas las respuestas con sus preguntas y categorías
        $answers = $evaluation->answers()->with(['question.category'])->get();
        
        foreach ($answers as $answer) {
            $question = $answer->question;
            if ($question && $question->category) {
                $questionsByCategory[$question->category->name][] = [
                    'question' => $question->question_text,
                    'answer' => $answer->answer_value ?? 'Sin respuesta',
                    'points' => $answer->points_earned ?? 0,
                    'question_type' => $question->question_type
                ];
            }
        }
    
        $pdf = Pdf::loadView('pdf.evaluation-report', [
            'evaluation' => $evaluation,
            'report' => $report,
            'companyName' => $companyName,
            'questionsByCategory' => $questionsByCategory
        ]);
    
        $fileName = 'Evaluacion_previa_' . str_replace(' ', '_', $evaluation->user->name) . '.pdf';
        
        return $pdf->download($fileName);
    }
}