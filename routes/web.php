<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagCategoryController;
use App\Http\Controllers\CategoryController;

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


// Rutas de administración
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::resource('tags', TagCategoryController::class);
    // Route::resource('categories', CategoryController::class);
    // API para obtener tags (para selects en formularios)
    Route::get('api/tags', [TagCategoryController::class, 'apiIndex'])->name('api.tags.index');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


