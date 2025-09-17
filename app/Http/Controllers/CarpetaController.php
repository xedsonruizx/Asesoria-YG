<?php

namespace App\Http\Controllers;

use App\Models\Carpeta;
use App\Http\Requests\StoreCarpetaRequest;
use App\Http\Requests\UpdateCarpetaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CarpetaController extends Controller
{
    public function index(Request $request)
    {
        $query = Carpeta::with(['padre', 'subcarpetas' => function ($query) {
            $query->activas();
        }])
        ->withCount([
            'bibliotecas as bibliotecas_count' => function ($query) {
                $query->whereNull('deleted_at');
            },
            'subcarpetas as subcarpetas_count'
        ]);
    
        // Filtrar por carpeta padre si se especifica
        if ($request->has('padre_id')) {
            $query->where('padre_id', $request->padre_id);
        } else {
            // Por defecto mostrar solo carpetas raíz
            $query->raiz();
        }
    
        $carpetas = $query->ordenadas()->get();
    
        // Si es una petición AJAX, devolver JSON
        if ($request->expectsJson()) {
            return response()->json([
                'carpetas' => $carpetas,
                'stats' => [
                    'total' => Carpeta::count(),
                    'activas' => Carpeta::activas()->count(),
                    'inactivas' => Carpeta::where('activa', false)->count(),
                    'raiz' => Carpeta::raiz()->count(),
                ]
            ]);
        }
    
        // Si no es AJAX, redirigir a biblioteca
        return redirect()->route('admin.biblioteca.index')
            ->with('info', 'Las carpetas se gestionan desde el modal de jerarquía.');
   
        $query = Carpeta::with(['padre', 'subcarpetas' => function ($query) {
            $query->activas();
        }])
        ->withCount([
            'bibliotecas as bibliotecas_count' => function ($query) {
                $query->whereNull('deleted_at');
            },
            'subcarpetas as subcarpetas_count'
        ]);
    
        // Filtrar por carpeta padre si se especifica
        if ($request->has('padre_id')) {
            $query->where('padre_id', $request->padre_id);
        } else {
            // Por defecto mostrar solo carpetas raíz
            $query->raiz();
        }

        $carpetas = $query->ordenadas()->paginate(15);

        // Obtener carpeta padre actual para breadcrumbs
        $carpetaPadre = null;
        if ($request->has('padre_id') && $request->padre_id) {
            $carpetaPadre = Carpeta::with('padre')->find($request->padre_id);
        }

        return Inertia::render('administration/Carpetas/Index', [
            'carpetas' => $carpetas,
            'carpetaPadre' => $carpetaPadre,
            'breadcrumbs' => $carpetaPadre ? $carpetaPadre->getRutaPadres()->push($carpetaPadre) : collect(),
            'stats' => [
                'total' => Carpeta::count(),
                'activas' => Carpeta::activas()->count(),
                'inactivas' => Carpeta::where('activa', false)->count(),
                'raiz' => Carpeta::raiz()->count(),
            ]
        ]);
    }

    public function create(Request $request)
    {
        // Obtener carpetas disponibles para ser padre (excluyendo la actual si es edición)
        $carpetasDisponibles = Carpeta::activas()
            ->ordenadas()
            ->get()
            ->map(function ($carpeta) {
                return [
                    'id' => $carpeta->id,
                    'nombre' => $carpeta->ruta_completa,
                    'nivel' => $carpeta->nivel
                ];
            });

        $carpetaPadre = null;
        if ($request->has('padre_id') && $request->padre_id) {
            $carpetaPadre = Carpeta::find($request->padre_id);
        }

        return Inertia::render('administration/Carpetas/Create', [
            'carpetasDisponibles' => $carpetasDisponibles,
            'carpetaPadre' => $carpetaPadre
        ]);
    }

    public function store(StoreCarpetaRequest $request)
    {
        $validatedData = $request->validated();
    
        $carpeta = Carpeta::create($validatedData);
    
        return redirect()->back()->with('success', 'Carpeta creada exitosamente.');
    }

    public function show(Carpeta $carpeta)
    {
        $carpeta->load([
            'padre',
            'subcarpetas' => function ($query) {
                $query->activas()->ordenadas();
            },
            'bibliotecas' => function ($query) {
                $query->with(['padre', 'hijos'])
                    ->whereNull('deleted_at')
                    ->ordenados();
            }
        ]);

        return Inertia::render('administration/Carpetas/Show', [
            'carpeta' => $carpeta,
            'breadcrumbs' => $carpeta->getRutaPadres()->push($carpeta)
        ]);
    }

    public function edit(Carpeta $carpeta)
    {
        // Obtener carpetas disponibles para ser padre (excluyendo la actual y sus descendientes)
        $carpetasDisponibles = Carpeta::activas()
            ->where('id', '!=', $carpeta->id)
            ->ordenadas()
            ->get()
            ->filter(function ($c) use ($carpeta) {
                // Excluir descendientes para evitar referencias circulares
                return !$this->esDescendiente($c, $carpeta);
            })
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'nombre' => $c->ruta_completa,
                    'nivel' => $c->nivel
                ];
            })
            ->values();

        return Inertia::render('administration/Carpetas/Edit', [
            'carpeta' => $carpeta,
            'carpetasDisponibles' => $carpetasDisponibles
        ]);
    }

    public function update(UpdateCarpetaRequest $request, Carpeta $carpeta)
    {
        $validatedData = $request->validated();
    
        $carpeta->update($validatedData);
    
        return redirect()->back()->with('success', 'Carpeta actualizada exitosamente.');
    }

    public function destroy(Carpeta $carpeta)
    {
        try {
            // Eliminar recursivamente de forma definitiva (no soft delete)
            $this->eliminarCarpetaRecursivaDefinitiva($carpeta);
            
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Carpeta eliminada definitivamente'
                ]);
            }
            
            return redirect()->back()->with('success', 'Carpeta eliminada definitivamente');
            
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar la carpeta: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error al eliminar la carpeta: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar carpeta recursivamente de forma definitiva (no soft delete)
     */
    private function eliminarCarpetaRecursivaDefinitiva(Carpeta $carpeta)
    {
        // 1. Eliminar todas las subcarpetas recursivamente
        foreach ($carpeta->subcarpetas as $subcarpeta) {
            $this->eliminarCarpetaRecursivaDefinitiva($subcarpeta);
        }
        
        // 2. Mover elementos de biblioteca a la carpeta padre (o null si es raíz)
        $carpetaPadreId = $carpeta->padre_id;
        
        // Actualizar elementos de biblioteca para moverlos a la carpeta padre
        \DB::table('biblioteca')
            ->where('carpeta_id', $carpeta->id)
            ->update(['carpeta_id' => $carpetaPadreId]);
        
        // 3. Eliminar definitivamente la carpeta actual (forceDelete)
        $carpeta->forceDelete();
    }

    public function toggle(Carpeta $carpeta)
    {
        $carpeta->update(['activa' => !$carpeta->activa]);

        $status = $carpeta->activa ? 'activada' : 'desactivada';
        
        return redirect()->back()
            ->with('success', "Carpeta {$status} exitosamente.");
    }

    // Método auxiliar para verificar si una carpeta es descendiente de otra
    private function esDescendiente(Carpeta $posibleDescendiente, Carpeta $ancestro)
    {
        $actual = $posibleDescendiente;
        
        while ($actual && $actual->padre_id) {
            if ($actual->padre_id == $ancestro->id) {
                return true;
            }
            $actual = $actual->padre;
        }
        
        return false;
    }

    // API para obtener árbol jerárquico (útil para componentes Vue)
    public function arbol()
    {
        $arbol = Carpeta::getArbolJerarquico();
        
        return response()->json($arbol);
    }
}