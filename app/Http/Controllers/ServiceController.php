<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    /**
     * Mostrar servicios disponibles para clientes
     */
    public function index(Request $request)
    {
        $query = Service::active()->ordered();

        // Filtrar por categoría
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $services = $query->get();
        $categories = Service::active()
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category');

        return Inertia::render('ClientMenu/Services/Index', [
            'services' => $services,
            'categories' => $categories,
            'filters' => $request->only(['category', 'search'])
        ]);
    }

    /**
     * Mostrar detalles de un servicio
     */
    public function show(Service $service)
    {
        if (!$service->is_active) {
            abort(404);
        }

        return Inertia::render('ClientMenu/Services/Show', [
            'service' => $service
        ]);
    }
}