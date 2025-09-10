<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationQuestion;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with(['roles', 'evaluations' => function($query) {
            $query->where('status', 'completed');
        }])->latest()->paginate(10);

        // Calcular puntos para cada usuario
        $users->getCollection()->transform(function ($user) {
            // Obtener todas las respuestas de evaluaciones completadas del usuario
            $totalPoints = EvaluationAnswer::whereHas('evaluation', function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('status', 'completed');
            })
            ->whereHas('question', function($query) {
                // Solo contar preguntas que tienen más de 1 punto
                // Esto incluye tanto preguntas con puntos base > 1 como preguntas con opciones que tienen > 1 punto
                $query->where(function($q) {
                    $q->where('points', '>', 1)
                      ->orWhere(function($subQ) {
                          // Para preguntas con opciones que tienen puntos individuales
                          $subQ->whereIn('question_type', ['select', 'radio', 'checkbox'])
                               ->whereRaw('JSON_EXTRACT(options, "$[*].points") IS NOT NULL')
                               ->whereRaw('JSON_EXTRACT(options, "$[*].points") REGEXP "[2-9]|[1-9][0-9]+"');
                      });
                });
            })
            ->where('points_earned', '>', 1) // Solo contar respuestas que efectivamente obtuvieron más de 1 punto
            ->sum('points_earned');

            $user->total_evaluation_points = $totalPoints;
            return $user;
        });

        return Inertia::render('administration/Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all(['id', 'name']);
        
        return Inertia::render('administration/Users/Create', [
            'availableRoles' => $roles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'ispremium' => 'boolean',
            'role' => 'required|string|exists:roles,name'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'ispremium' => $request->boolean('ispremium', false)
        ]);
        
        // Asignar el rol al usuario
        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with('roles')->findOrFail($id);
        
        return Inertia::render('administration/Users/Show', [
            'user' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::with('roles')->findOrFail($id);
        $roles = Role::all(['id', 'name']);
        
        return Inertia::render('administration/Users/Edit', [
            'user' => $user,
            'availableRoles' => $roles
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'ispremium' => 'boolean',
            'role' => 'required|string|exists:roles,name'
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
            'ispremium' => $request->boolean('ispremium', false)
        ]);
        
        // Sincronizar roles (elimina roles anteriores y asigna el nuevo)
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
