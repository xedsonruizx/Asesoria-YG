<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        // Capturar la URL intended del parámetro GET y guardarla en la sesión
        if ($request->has('intended')) {
            $request->session()->put('url.intended', $request->get('intended'));
        }
        
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Obtener la URL intended de la sesión
        $intendedUrl = $request->session()->get('url.intended');
        
        // Si hay una URL intended, redirigir ahí
        if ($intendedUrl) {
            // Limpiar la URL intended de la sesión
            $request->session()->forget('url.intended');
            return redirect($intendedUrl);
        }
        
        // Si no hay URL intended, redirigir según el rol del usuario
        $user = Auth::user();
        
        if ($user->hasPermissionTo('manage')) {
            return redirect()->route('dashboard');
        }
        
        // Para usuarios regulares, redirigir a la página principal
        return redirect()->route('inicio');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
