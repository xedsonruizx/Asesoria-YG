<?php

namespace App\Http\Controllers;

use App\Models\TagCategory;
use Illuminate\Http\Request;
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

        $tags = $query->withCount('posts')
                     ->orderBy('name')
                     ->paginate(15)
                     ->withQueryString();

        return Inertia::render('administration/Tags/Index', [
            'tags' => $tags,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function create()
    {
        return Inertia::render('administration/Tags/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags_category,name',
            'slug' => 'nullable|string|max:255|unique:tags_category,slug',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean'
        ]);

        // Generar slug si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        TagCategory::create($validated);

        return redirect()->route('tags.index')
                        ->with('success', 'Tag creado exitosamente.');
    }

    public function show(TagCategory $tag)
    {
        $tag->load(['posts' => function ($query) {
            $query->latest()->take(10);
        }]);

        return Inertia::render('administration/Tags/Show', [
            'tag' => $tag
        ]);
    }

    public function edit(TagCategory $tag)
    {
        return Inertia::render('administration/Tags/Edit', [
            'tag' => $tag
        ]);
    }

    public function update(Request $request, TagCategory $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags_category,name,' . $tag->id,
            'slug' => 'nullable|string|max:255|unique:tags_category,slug,' . $tag->id,
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean'
        ]);

        // Generar slug si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $tag->update($validated);

        return redirect()->route('tags.index')
                        ->with('success', 'Tag actualizado exitosamente.');
    }

    public function destroy(TagCategory $tag)
    {
        // Verificar si tiene posts asociados
        if ($tag->posts()->count() > 0) {
            return redirect()->route('tags.index')
                           ->with('error', 'No se puede eliminar el tag porque tiene posts asociados.');
        }

        $tag->delete();

        return redirect()->route('tags.index')
                        ->with('success', 'Tag eliminado exitosamente.');
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
