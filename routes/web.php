<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PostController;

// SECCION DE INVITADOS O CLIENTES
Route::redirect('/', '/inicio');
Route::get('/inicio', function () {return Inertia::render('LayoutMaster');})-> name('inicio');

Route::get('evaluacion', function () {return Inertia::render('ClientMenu/Evaluation');})-> name('evaluacion');

// Nueva ruta para mostrar posts individuales a clientes
Route::get('publicacion/{post}', [PostController::class, 'clientShow'])->name('publicacion.show');

Route::get('dashboard', function () {return Inertia::render('administration/Dashboard');})->middleware(['auth', 'verified'])->name('dashboard');



// Rutas para admin
Route::resource('posts', PostController::class);
Route::get('publicaciones', [PostController::class, 'AdminIndex'])-> name('publicaciones');
Route::patch('posts/{post}/status', [PostController::class, 'changeStatus'])->name('posts.change-status');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


