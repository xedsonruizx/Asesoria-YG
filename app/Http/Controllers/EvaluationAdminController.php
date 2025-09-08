<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Evaluation;
use App\Models\User;
use App\Models\EvaluationCategory;

class EvaluationAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $evaluations = Evaluation::with(['user'])
            ->latest()
            ->paginate(10);
    
        $stats = [
            'total' => Evaluation::count(),
            'completed' => Evaluation::where('status', 'completed')->count(),
            'in_progress' => Evaluation::where('status', 'in_progress')->count(),
            'draft' => Evaluation::where('status', 'draft')->count(),
        ];
    
        $categories = EvaluationCategory::active()->ordered()->get();
    
        return Inertia::render('administration/Evaluation/Index', [
            'evaluations' => $evaluations,
            'stats' => $stats,
            'categories' => $categories
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
        $evaluation = Evaluation::findOrFail($id);
        $evaluation->delete();

        return redirect()->route('admin.evaluations.index')
            ->with('success', 'Evaluación eliminada exitosamente.');
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
}