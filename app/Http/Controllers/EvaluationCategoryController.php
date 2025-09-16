<?php

namespace App\Http\Controllers;

use App\Models\EvaluationCategory;
use App\Http\Requests\StoreEvaluationCategoryRequest;
use App\Http\Requests\UpdateEvaluationCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Str;

class EvaluationCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = EvaluationCategory::query();

        // Filtro para mostrar eliminados
        if ($request->filled('show_deleted')) {
            if ($request->show_deleted === 'only') {
                $query->onlyTrashed();
            } elseif ($request->show_deleted === 'with') {
                $query->withTrashed();
            }
        }

        // Búsqueda
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $categories = $query->withCount('questions')
                           ->orderBy('name')
                           ->paginate(15)
                           ->withQueryString();

        return Inertia::render('administration/Question_Category/Index', [
            'categories' => [
                'data' => $categories->items(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem() ?? 0,
                'to' => $categories->lastItem() ?? 0,
            ],
            'filters' => $request->only(['search', 'status', 'show_deleted'])
        ]);
    }

    public function create()
    {
        return Inertia::render('administration/Question_Category/Create');
    }

    public function store(StoreEvaluationCategoryRequest $request)
    {
        $validated = $request->validated();
    
        // Generar slug si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Verificar que el slug generado sea único
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (EvaluationCategory::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }
    
        // Establecer is_active = true por defecto si no se proporciona
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }
    
        try {
            EvaluationCategory::create($validated);
            
            return redirect()->route('question-categories.index')
                            ->with('success', 'Categoría de evaluación creada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear categoría de evaluación: ' . $e->getMessage());
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error al crear la categoría. Por favor, inténtalo de nuevo.');
        }
    }

    public function show(EvaluationCategory $questionCategory)
    {
        $questionCategory->load(['questions' => function ($query) {
            $query->latest()->take(10);
        }]);

        return Inertia::render('administration/Question_Category/Show', [
            'category' => $questionCategory
        ]);
    }

    public function edit(EvaluationCategory $questionCategory)
    {
        return Inertia::render('administration/Question_Category/Edit', [
            'category' => $questionCategory
        ]);
    }

    public function update(UpdateEvaluationCategoryRequest $request, EvaluationCategory $questionCategory)
    {
        $validated = $request->validated();

        // Generar slug si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Verificar que el slug generado sea único (excluyendo el registro actual)
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (EvaluationCategory::where('slug', $validated['slug'])
                             ->where('id', '!=', $questionCategory->id)
                             ->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        try {
            $questionCategory->update($validated);
            
            return redirect()->route('question-categories.index')
                            ->with('success', 'Categoría de evaluación actualizada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar categoría de evaluación: ' . $e->getMessage());
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error al actualizar la categoría. Por favor, inténtalo de nuevo.');
        }
    }

    public function destroy(EvaluationCategory $evaluationCategory)
    {
        try {
            // Verificar si tiene preguntas asociadas
            if ($evaluationCategory->allQuestions()->count() > 0) {
                return redirect()->route('question-categories.index')
                               ->with('error', 'No se puede eliminar la categoría porque tiene preguntas asociadas.');
            }

            $evaluationCategory->delete(); // Soft delete

            return redirect()->route('question-categories.index')
                           ->with('success', 'Categoría eliminada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar categoría: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al eliminar la categoría.']);
        }
    }

    /**
     * Restore a soft deleted category
     */
    public function restore($id)
    {
        try {
            $category = EvaluationCategory::onlyTrashed()->findOrFail($id);
            $category->restore();

            return redirect()->route('question-categories.index')
                           ->with('success', 'Categoría restaurada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al restaurar categoría: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al restaurar la categoría.']);
        }
    }

    /**
     * Force delete a category
     */
    public function forceDelete($id)
    {
        try {
            $category = EvaluationCategory::onlyTrashed()->findOrFail($id);
            $category->forceDelete();

            return redirect()->route('question-categories.index')
                           ->with('success', 'Categoría eliminada permanentemente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar permanentemente categoría: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al eliminar permanentemente la categoría.']);
        }
    }

    /**
     * Toggle status of category
     */
    public function toggleStatus($id)
    {
        $category = EvaluationCategory::findOrFail($id);
        
        try {
            // Debug: Log antes de la actualización
            Log::info('Intentando actualizar estado de categoría', [
                'category_id' => $category->id,
                'from_status' => $category->is_active,
                'to_status' => !$category->is_active
            ]);

            $category->update([
                'is_active' => !$category->is_active
            ]);

            // Debug: Log después de la actualización
            $category->refresh();
            Log::info('Estado actualizado exitosamente', [
                'category_id' => $category->id,
                'updated_status' => $category->is_active
            ]);
    
            $status = $category->is_active ? 'activada' : 'desactivada';
            
        
            
            return redirect()->route('question-categories.index')
                           ->with('success', "Categoría {$status} exitosamente.");
        } catch (\Exception $e) {
 
            return back()->withErrors(['error' => 'Error al cambiar el estado de la categoría.']);
        }
    }

    // API para obtener categorías activas (para selects)
    public function apiIndex(Request $request)
    {
        $query = EvaluationCategory::active();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $categories = $query->select('id', 'name', 'color')
                     ->get();

        return response()->json($categories);
    }
}