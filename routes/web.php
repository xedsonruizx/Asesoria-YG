<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagCategoryController;
use App\Http\Controllers\CategoryController;

// ============================================
// RUTAS PÚBLICAS (SIN AUTENTICACIÓN)
// ============================================
Route::redirect('/', '/inicio');
Route::get('/inicio', function () {return Inertia::render('LayoutMaster');})-> name('inicio');
Route::get('evaluacion', function () {return Inertia::render('ClientMenu/Evaluation');})-> name('evaluacion');
Route::get('publicacion/{post}', [PostController::class, 'clientShow'])->name('publicacion.show');

// ============================================
// RUTAS PARA USUARIOS AUTENTICADOS
// ============================================
Route::middleware(['auth'])->group(function () {
    // Rutas que requieren permiso 'manage'
    Route::middleware(['permission:manage'])->group(function () {
        // Resource completo para posts (incluye create, store, edit, etc.)
        Route::resource('posts', PostController::class);
        
        // Rutas adicionales para posts
        Route::post('posts/{post}/update-with-files', [PostController::class, 'updateWithFiles'])->name('posts.update-files');
        Route::patch('posts/{post}/status', [PostController::class, 'changeStatus'])->name('posts.change-status');
        Route::delete('/posts/{post}/remove-image', [PostController::class, 'removeImage'])->name('posts.remove-image');
        Route::delete('/posts/{post}/remove-file', [PostController::class, 'removeFile'])->name('posts.remove-file');
        
        // Vista administrativa de publicaciones
        Route::get('publicaciones', [PostController::class, 'AdminIndex'])->name('publicaciones');
        
        // Resource para tags
        Route::resource('tags', TagCategoryController::class);
        Route::get('api/tags', [TagCategoryController::class, 'apiIndex'])->name('api.tags.index');
    });
    
    // Rutas que requieren permiso 'guest' (solo ver)
    Route::middleware(['permission:guest'])->group(function () {
        // Rutas de solo lectura si las necesitas
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


