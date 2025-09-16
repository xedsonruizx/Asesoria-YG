<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagCategoryController;
use App\Http\Controllers\PostCategoryController; // Agregar esta línea
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\EvaluationAdminController;
use App\Http\Controllers\EvaluationQuestionController;
use App\Http\Controllers\EvaluationCategoryController; // Agregar esta línea
use App\Http\Controllers\MultaController;

// ============================================
// RUTAS PÚBLICAS (SIN AUTENTICACIÓN)
// ============================================
Route::redirect('/', '/inicio');
Route::get('/inicio', function () {return Inertia::render('LayoutMaster');})-> name('inicio');
Route::get('publicaciones', [PostController::class, 'index'])->name('publicaciones');
Route::get('publicacion/{post}', [PostController::class, 'clientShow'])->name('publicacion.show');

// ============================================
// RUTAS PARA USUARIOS AUTENTICADOS
// ============================================
Route::middleware(['auth'])->group(function () {
    // Ruta de evaluación que requiere autenticación
    Route::get('evaluacion', [EvaluationController::class, 'index'])->name('evaluacion');
    
    // Rutas de API para evaluaciones
    Route::post('/evaluation/answer', [EvaluationController::class, 'saveAnswer'])->name('evaluation.answer');
    Route::post('/evaluation/submit', [EvaluationController::class, 'submit'])->name('evaluation.submit');
    Route::post('/evaluation/restart', [EvaluationController::class, 'restart'])->name('evaluation.restart');
    Route::get('/evaluation/{evaluation}/report', [EvaluationController::class, 'report'])->name('evaluation.report');
    
    // Rutas que requieren permiso 'manage'
    Route::middleware(['permission:manage'])->group(function () {
        Route::get('dashboard', function () {return Inertia::render('administration/Dashboard');})-> name('dashboard');
        Route::resource('posts', PostController::class);
        Route::resource('users', UserController::class);

        // Rutas adicionales para posts
        Route::post('posts/{post}/update-with-files', [PostController::class, 'updateWithFiles'])->name('posts.update-files');
        Route::patch('posts/{post}/status', [PostController::class, 'changeStatus'])->name('posts.change-status');
        Route::patch('posts/{post}/toggle-status', [PostController::class, 'toggleStatus'])->name('posts.toggle-status');
        Route::patch('posts/{id}/restore', [PostController::class, 'restore'])->name('posts.restore');
        Route::delete('posts/{id}/force-delete', [PostController::class, 'forceDelete'])->name('posts.force-delete');
        Route::delete('/posts/{post}/remove-image', [PostController::class, 'removeImage'])->name('posts.remove-image');
        Route::delete('/posts/{post}/remove-file', [PostController::class, 'removeFile'])->name('posts.remove-file');
    
        // Cambiar el nombre para evitar duplicación
        Route::get('admin/posts', [PostController::class, 'AdminIndex'])->name('posts.admin');
        
        // Resource para tags
        Route::resource('tags', TagCategoryController::class);
        Route::patch('tags/{tag}/toggle-status', [TagCategoryController::class, 'toggleStatus'])->name('tags.toggle-status');
        Route::patch('tags/{id}/restore', [TagCategoryController::class, 'restore'])->name('tags.restore');
        Route::delete('tags/{id}/force-delete', [TagCategoryController::class, 'forceDelete'])->name('tags.force-delete');
        Route::get('api/tags', [TagCategoryController::class, 'apiIndex'])->name('api.tags.index');
        
        // Resource para categorías de posts
        Route::resource('post-categories', TagCategoryController::class);
        Route::get('api/post-categories', [TagCategoryController::class, 'apiIndex'])->name('api.post-categories.index');

        // Resource para categorías de evaluación
        // MOVER las rutas específicas ANTES del Route::resource
        Route::patch('question-categories/{id}/toggle-status', [EvaluationCategoryController::class, 'toggleStatus'])->name('question-categories.toggle-status');
        Route::patch('question-categories/{id}/restore', [EvaluationCategoryController::class, 'restore'])->name('question-categories.restore');
        Route::delete('question-categories/{id}/force-delete', [EvaluationCategoryController::class, 'forceDelete'])->name('question-categories.force-delete');
        Route::resource('question-categories', EvaluationCategoryController::class);
        Route::get('api/question-categories', [EvaluationCategoryController::class, 'apiIndex'])->name('api.question-categories.index');

        // Rutas administrativas para evaluaciones
        Route::resource('evaluations', EvaluationAdminController::class)->names([
            'index' => 'admin.evaluations.index',
            'create' => 'admin.evaluations.create',
            'store' => 'admin.evaluations.store',
            'show' => 'admin.evaluations.show',
            'edit' => 'admin.evaluations.edit',
            'update' => 'admin.evaluations.update',
            'destroy' => 'admin.evaluations.destroy'
        ]);
        Route::post('evaluations/{evaluation}/reset', [EvaluationAdminController::class, 'reset'])->name('admin.evaluations.reset');
        Route::patch('evaluations/{evaluation}/toggle-status', [EvaluationAdminController::class, 'toggleStatus'])->name('admin.evaluations.toggle-status');
        Route::patch('evaluations/{id}/restore', [EvaluationAdminController::class, 'restore'])->name('admin.evaluations.restore');
        Route::delete('evaluations/{id}/force-delete', [EvaluationAdminController::class, 'forceDelete'])->name('admin.evaluations.force-delete');
        Route::get('/evaluations/{id}/pdf', [EvaluationAdminController::class, 'generatePdf'])->name('admin.evaluations.pdf');
    
        // Rutas específicas ANTES del resource route
        Route::get('admin/questions/next-order', [EvaluationQuestionController::class, 'getNextOrder'])->name('admin.questions.next-order');
        Route::get('admin/questions/by-category', [EvaluationQuestionController::class, 'getQuestionsByCategory'])->name('admin.questions.by-category');
        Route::patch('admin/questions/{question}/toggle-status', [EvaluationQuestionController::class, 'toggleStatus'])->name('admin.questions.toggle-status');
        
        // Resource route DESPUÉS de las rutas específicas
        Route::resource('admin/questions', EvaluationQuestionController::class)->names([
            'index' => 'admin.questions.index',
            'create' => 'admin.questions.create',
            'store' => 'admin.questions.store',
            'show' => 'admin.questions.show',
            'edit' => 'admin.questions.edit',
            'update' => 'admin.questions.update',
            'destroy' => 'admin.questions.destroy',
        ]);
        // Agregar estas rutas dentro del grupo de administración
        Route::patch('/admin/questions/{id}/restore', [EvaluationQuestionController::class, 'restore'])->name('admin.questions.restore');
        Route::delete('/admin/questions/{id}/force-delete', [EvaluationQuestionController::class, 'forceDelete'])->name('admin.questions.force-delete');
    });
    
    // Rutas que requieren permiso 'guest' (solo ver)
    Route::middleware(['permission:guest'])->group(function () {
        // Rutas de solo lectura si las necesitas
        // Resource para multas
        Route::resource('multas', MultaController::class);
        Route::patch('/multas/{multa}/toggle-status', [MultaController::class, 'toggleStatus'])->name('multas.toggle-status');
        Route::patch('/multas/{id}/restore', [MultaController::class, 'restore'])->name('multas.restore');
        Route::delete('/multas/{id}/force-delete', [MultaController::class, 'forceDelete'])->name('multas.force-delete');
        Route::get('api/multas', [MultaController::class, 'apiIndex'])->name('api.multas.index');
        Route::delete('/multas/{multa}/remove-file', [MultaController::class, 'removeFile'])->name('multas.remove-file');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


