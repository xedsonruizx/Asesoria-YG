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
// RUTAS PARA USUARIOS CON PERMISO DE VER POSTS
// ============================================
Route::middleware(['auth', 'role:admin|editor'])->group(function () {
    Route::get('publicaciones', [PostController::class, 'AdminIndex'])->name('publicaciones');
    Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('dashboard', function () {return Inertia::render('administration/Dashboard');})->name('dashboard');
});




// Rutas para admin
Route::resource('posts', PostController::class);
// Ruta POST específica para actualización con archivos
Route::post('posts/{post}/update-with-files', [PostController::class, 'updateWithFiles'])->name('posts.update-files');
Route::get('publicaciones', [PostController::class, 'AdminIndex'])-> name('publicaciones');
Route::patch('posts/{post}/status', [PostController::class, 'changeStatus'])->name('posts.change-status');


// Rutas de administración
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::resource('tags', TagCategoryController::class);
    Route::get('api/tags', [TagCategoryController::class, 'apiIndex'])->name('api.tags.index');
    Route::delete('/posts/{post}/remove-image', [PostController::class, 'removeImage'])->name('posts.remove-image');
    Route::delete('/posts/{post}/remove-file', [PostController::class, 'removeFile'])->name('posts.remove-file');
});
// Rutas protegidas por roles
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return 'Panel de administración';
    });
});

// Rutas protegidas por permisos específicos
Route::middleware(['auth', 'permission:edit posts'])->group(function () {
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}/remove-image', [PostController::class, 'removeImage'])->name('posts.remove-image');
});

Route::middleware(['auth', 'permission:delete posts'])->group(function () {
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


