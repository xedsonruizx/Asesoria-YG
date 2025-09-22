<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AuthSettingsController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function profile(Request $request): Response
    {
        return Inertia::render('ClientMenu/AuthSettings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->fill($request->only('name', 'email'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('auth-settings.profile')
            ->with('status', 'Perfil actualizado exitosamente.');
    }

    /**
     * Show the user's password settings page.
     */
    public function password(): Response
    {
        return Inertia::render('ClientMenu/AuthSettings/Password');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('auth-settings.password')
            ->with('status', 'Contraseña actualizada exitosamente.');
    }

    /**
     * Show the user's appearance settings page.
     */
    public function appearance(): Response
    {
        return Inertia::render('ClientMenu/AuthSettings/Appearance');
    }
}