<?php

namespace App\Http\Controllers;

use App\Models\TagCategory;
use App\Http\Requests\StoreTagCategoryRequest;
use App\Http\Requests\UpdateTagCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Str;

class TagCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = TagCategory::query();

        // Búsqueda
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $categories = $query->withCount('posts')
                           ->orderBy('name')
                           ->paginate(15)
                           ->withQueryString();

        return Inertia::render('administration/Post_category/Index', [
            'categories' => [
                'data' => $categories->items(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem() ?? 0,
                'to' => $categories->lastItem() ?? 0,
            ],
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function create()
    {
        return Inertia::render('administration/Post_category/Create');
    }

    public function store(StoreTagCategoryRequest $request)
    {
        $validated = $request->validated();

        // Generar slug si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Verificar que el slug generado sea único
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (TagCategory::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        try {
            TagCategory::create($validated);
            
            return redirect()->route('post-categories.index')
                            ->with('success', 'Categoría creada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear categoría: ' . $e->getMessage());
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error al crear la categoría. Por favor, inténtalo de nuevo.');
        }
    }

    public function show(TagCategory $postCategory)
    {
        $postCategory->load(['posts' => function ($query) {
            $query->latest()->take(10);
        }]);

        return Inertia::render('administration/Post_category/Show', [
            'category' => $postCategory
        ]);
    }

    public function edit(TagCategory $postCategory)
    {
        return Inertia::render('administration/Post_category/Edit', [
            'category' => $postCategory
        ]);
    }

    public function update(UpdateTagCategoryRequest $request, TagCategory $postCategory)
    {
        $validated = $request->validated();

        // Generar slug si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Verificar que el slug generado sea único (excluyendo el registro actual)
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (TagCategory::where('slug', $validated['slug'])
                             ->where('id', '!=', $postCategory->id)
                             ->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        try {
            $postCategory->update($validated);
            
            return redirect()->route('post-categories.index')
                            ->with('success', 'Categoría actualizada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar categoría: ' . $e->getMessage());
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error al actualizar la categoría. Por favor, inténtalo de nuevo.');
        }
    }

    public function destroy(TagCategory $post_category)  // Cambiar de $postCategory a $post_category
    {
        // Verificar si tiene posts asociados
        if ($post_category->posts()->count() > 0) {
            return redirect()->route('post-categories.index')
                           ->with('error', 'No se puede eliminar la categoría porque tiene posts asociados.');
        }
    
        $post_category->delete();
    
        return redirect()->route('post-categories.index')
                        ->with('success', 'Categoría eliminada exitosamente.');
    }

    // API para obtener tags activos (para selects)
    public function apiIndex(Request $request)
    {
        $query = TagCategory::active();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $tags = $query->select('id', 'name', 'color')
                     ->orderBy('name')
                     ->get();

        return response()->json($tags);
    }
}
