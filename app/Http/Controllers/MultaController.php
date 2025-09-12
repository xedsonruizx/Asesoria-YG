<?php

namespace App\Http\Controllers;

use App\Models\Multa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class MultaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Multa::query();

        // Filtro para mostrar eliminados
        if ($request->filled('show_deleted') && $request->show_deleted === 'true') {
            $query->onlyTrashed();
        } else {
            // Si no se quieren mostrar eliminados, excluirlos explícitamente
            $query->whereNull('deleted_at');
        }

        // Filtro por estado
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->inactive();
            }
        }

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $multas = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('administration/Multas/Index', [
            'multas' => $multas,
            'filters' => $request->only(['search', 'status', 'show_deleted'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('administration/MultaForm');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'is_active' => 'boolean',
            'file' => 'nullable|file|mimes:pdf,doc,docx,txt,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        try {
            $multa = new Multa();
            $multa->name = $validated['name'];
            $multa->description = $validated['description'];
            $multa->is_active = $validated['is_active'] ?? true;

            // Manejar la subida del archivo
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('multas', $fileName, 'public');
                $multa->file_path = $filePath;
            }

            $multa->save();

            return redirect()->route('multas.index')
                           ->with('success', 'Multa creada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear multa: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al crear la multa.'])->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Multa $multa)
    {
        return Inertia::render('administration/MultaShow', [
            'multa' => $multa
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Multa $multa)
    {
        return Inertia::render('administration/MultaForm', [
            'multa' => $multa
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Multa $multa)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'is_active' => 'boolean',
            'file' => 'nullable|file|mimes:pdf,doc,docx,txt,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        try {
            $multa->name = $validated['name'];
            $multa->description = $validated['description'];
            $multa->is_active = $validated['is_active'] ?? $multa->is_active;

            // Manejar la subida del nuevo archivo
            if ($request->hasFile('file')) {
                // Eliminar el archivo anterior si existe
                if ($multa->file_path && Storage::disk('public')->exists($multa->file_path)) {
                    Storage::disk('public')->delete($multa->file_path);
                }

                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('multas', $fileName, 'public');
                $multa->file_path = $filePath;
            }

            $multa->save();

            return redirect()->route('multas.index')
                           ->with('success', 'Multa actualizada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar multa: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al actualizar la multa.'])->withInput();
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus(Multa $multa)
    {
        try {
            $multa->is_active = !$multa->is_active;
            $multa->save();

            $status = $multa->is_active ? 'activada' : 'desactivada';
            return redirect()->route('multas.index', request()->only(['search', 'status', 'show_deleted', 'page']))
                           ->with('success', "Multa {$status} exitosamente.");
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de multa: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al cambiar el estado de la multa.']);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Multa $multa)
    {
        try {
            // TODO: Verificar si tiene preguntas asociadas cuando se implemente la relación
            // if ($multa->evaluationQuestions()->count() > 0) {
            //     return redirect()->route('multas.index')
            //                    ->with('error', 'No se puede eliminar la multa porque tiene preguntas asociadas.');
            // }

            $multa->delete(); // Soft delete

            return redirect()->route('multas.index', request()->only(['search', 'status', 'show_deleted', 'page']))
                           ->with('success', 'Multa eliminada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar multa: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al eliminar la multa.']);
        }
    }

    /**
     * Restore the specified resource from soft delete.
     */
    public function restore($id)
    {
        try {
            $multa = Multa::onlyTrashed()->findOrFail($id);
            $multa->restore();

            return redirect()->route('multas.index', request()->only(['search', 'status', 'show_deleted', 'page']))
                           ->with('success', 'Multa restaurada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al restaurar multa: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al restaurar la multa.']);
        }
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete($id)
    {
        try {
            $multa = Multa::onlyTrashed()->findOrFail($id);
            
            // Eliminar el archivo asociado si existe
            if ($multa->file_path && Storage::disk('public')->exists($multa->file_path)) {
                Storage::disk('public')->delete($multa->file_path);
            }

            $multa->forceDelete();

            return redirect()->route('multas.index', request()->only(['search', 'status', 'show_deleted', 'page']))
                           ->with('success', 'Multa eliminada permanentemente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar permanentemente multa: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al eliminar permanentemente la multa.']);
        }
    }

    /**
     * Remove file from multa
     */
    public function removeFile(Multa $multa)
    {
        try {
            if ($multa->file_path && Storage::disk('public')->exists($multa->file_path)) {
                Storage::disk('public')->delete($multa->file_path);
                $multa->file_path = null;
                $multa->save();
            }

            return response()->json(['success' => true, 'message' => 'Archivo eliminado exitosamente.']);
        } catch (\Exception $e) {
            Log::error('Error al eliminar archivo de multa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar el archivo.'], 500);
        }
    }

    /**
     * API endpoint para obtener multas activas (para selects, etc.)
     */
    public function apiIndex()
    {
        $multas = Multa::active()
                      ->select('id', 'name', 'description')
                      ->orderBy('name')
                      ->get();

        return response()->json($multas);
    }
}