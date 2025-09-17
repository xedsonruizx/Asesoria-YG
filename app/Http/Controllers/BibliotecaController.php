<?php

namespace App\Http\Controllers;

use App\Models\Biblioteca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BibliotecaController extends Controller
{
    /**
     * Mostrar la biblioteca principal con sidebar
     */
    public function index()
    {
        $bibliotecaTree = Biblioteca::raiz()
            ->with('hijosRecursivos')
            ->orderBy('orden')
            ->get();

        return Inertia::render('Biblioteca/Index', [
            'bibliotecaTree' => $bibliotecaTree
        ]);
    }

    /**
     * Mostrar un elemento específico de la biblioteca
     */
    public function show($slug)
    {
        $elemento = Biblioteca::where('slug', $slug)->firstOrFail();

        // Verificar acceso premium
        if ($elemento->is_premium && !Auth::user()?->hasRole('premium')) {
            return redirect()->route('biblioteca.index')
                ->with('error', 'Este contenido requiere suscripción premium');
        }

        $bibliotecaTree = Biblioteca::raiz()
            ->with('hijosRecursivos')
            ->orderBy('orden')
            ->get();

        return Inertia::render('Biblioteca/Show', [
            'elemento' => $elemento->load(['padre', 'hijos']),
            'bibliotecaTree' => $bibliotecaTree,
            'rutaCompleta' => $elemento->ruta_completa
        ]);
    }

    /**
     * Mostrar formulario de creación (Admin)
     */
    public function create()
    {

        $elementosPadre = Biblioteca::select('id', 'titulo', 'padre_id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'titulo' => $item->ruta_completa,
                    'nivel' => $item->nivel
                ];
            })
            ->sortBy('titulo');

        return Inertia::render('Admin/Biblioteca/Create', [
            'elementosPadre' => $elementosPadre
        ]);
    }

    /**
     * Almacenar nuevo elemento (Admin)
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:biblioteca,slug',
            'descripcion' => 'required|string',
            'padre_id' => 'nullable|exists:biblioteca,id',
            'is_premium' => 'boolean',
            'orden' => 'integer|min:0'
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['titulo']);
        }

        $biblioteca = Biblioteca::create($validated);

        return redirect()->route('admin.biblioteca.index')
            ->with('success', 'Elemento de biblioteca creado exitosamente');
    }

    /**
     * Mostrar formulario de edición (Admin)
     */
    public function edit(Biblioteca $biblioteca)
    {

        $elementosPadre = Biblioteca::where('id', '!=', $biblioteca->id)
            ->select('id', 'titulo', 'padre_id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'titulo' => $item->ruta_completa,
                    'nivel' => $item->nivel
                ];
            })
            ->sortBy('titulo');

        return Inertia::render('Admin/Biblioteca/Edit', [
            'biblioteca' => $biblioteca,
            'elementosPadre' => $elementosPadre
        ]);
    }

    /**
     * Actualizar elemento (Admin)
     */
    public function update(Request $request, Biblioteca $biblioteca)
    {

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'slug' => 'required|string|unique:biblioteca,slug,' . $biblioteca->id,
            'descripcion' => 'required|string',
            'padre_id' => 'nullable|exists:biblioteca,id',
            'is_premium' => 'boolean',
            'orden' => 'integer|min:0'
        ]);

        $biblioteca->update($validated);

        return redirect()->route('admin.biblioteca.index')
            ->with('success', 'Elemento de biblioteca actualizado exitosamente');
    }

    /**
     * Eliminar elemento (Admin)
     */
    public function destroy(Biblioteca $biblioteca)
    {

        if ($biblioteca->tieneHijos()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar un elemento que tiene hijos');
        }

        $biblioteca->delete();

        return redirect()->route('admin.biblioteca.index')
            ->with('success', 'Elemento de biblioteca eliminado exitosamente');
    }

    /**
     * Panel de administración
     */
    public function adminIndex()
    {

        $biblioteca = Biblioteca::with(['padre'])
            ->withTrashed()
            ->orderBy('orden')
            ->paginate(20);

        return Inertia::render('administration/Biblioteca/Index', [
            'biblioteca' => $biblioteca
        ]);
    }

    /**
     * Restaurar elemento eliminado
     */
    public function restore($id)
    {
        $biblioteca = Biblioteca::withTrashed()->findOrFail($id);

        $biblioteca->restore();

        return redirect()->back()
            ->with('success', 'Elemento restaurado exitosamente');
    }
}