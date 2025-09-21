<?php

use App\Http\Controllers\BibliotecaController;
use App\Http\Controllers\CarpetaController;
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
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ServiceController;
use App\Http\Middleware\ThrottleAnswers;

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
    Route::post('/evaluation/answer', [EvaluationController::class, 'saveAnswer'])->middleware(['auth']);
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
        Route::patch('post-categories/{tag}/toggle-status', [TagCategoryController::class, 'toggleStatus'])->name('post-category.toggle-status');
        Route::patch('post-categories/{id}/restore', [TagCategoryController::class, 'restore'])->name('post-category.restore');
        Route::delete('post-categories/{id}/force-delete', [TagCategoryController::class, 'forceDelete'])->name('post-category.force-delete');

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



        // Carpetas (mover dentro del grupo de administración)
        Route::resource('carpetas', CarpetaController::class)->names([
            'index' => 'carpetas.index',
            'create' => 'carpetas.create',
            'store' => 'carpetas.store',
            'show' => 'carpetas.show',
            'edit' => 'carpetas.edit',
            'update' => 'carpetas.update',
            'destroy' => 'carpetas.destroy',
        ]);
        Route::patch('carpetas/{carpeta}/toggle', [CarpetaController::class, 'toggle'])->name('carpetas.toggle');
        Route::get('api/carpetas/arbol', [CarpetaController::class, 'arbol'])->name('api.carpetas.arbol');





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

// Rutas de servicios y pagos (requieren autenticación)
Route::middleware(['auth'])->group(function () {
    // Rutas públicas de servicios (dentro de auth para acceso completo)
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    
    // Rutas de compras
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('show');
    });
    
    // Rutas de pagos
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/return', [PaymentController::class, 'return'])->name('return');
        Route::get('/stats', [PaymentController::class, 'stats'])->name('stats');
        Route::get('/success/{payment}', [PaymentController::class, 'success'])->name('success');
        Route::get('/failed/{payment}', [PaymentController::class, 'failed'])->name('failed');
        Route::post('/{payment}/retry', [PaymentController::class, 'retry'])->name('retry');
        Route::post('/{payment}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
        Route::get('/{payment}/receipt', [PaymentController::class, 'downloadReceipt'])->name('receipt');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    });
});

// Rutas públicas de biblioteca
Route::prefix('biblioteca')->name('biblioteca.')->group(function () {
    Route::get('/', [BibliotecaController::class, 'clientIndex'])->name('index');
    Route::get('/{slug}', [BibliotecaController::class, 'show'])->name('show');
});

Route::middleware(['auth'])->group(function () {
    // Rutas de administración de biblioteca
    Route::prefix('admin/biblioteca')->name('admin.biblioteca.')->middleware('role:admin')->group(function () {
        Route::get('/', [BibliotecaController::class, 'adminIndex'])->name('index');
        Route::get('/create', [BibliotecaController::class, 'create'])->name('create');
        Route::post('/', [BibliotecaController::class, 'store'])->name('store');
        Route::get('/{biblioteca}/edit', [BibliotecaController::class, 'edit'])->name('edit');
        Route::put('/{biblioteca}', [BibliotecaController::class, 'update'])->name('update');
        Route::delete('/{biblioteca}', [BibliotecaController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [BibliotecaController::class, 'restore'])->name('restore');
    });
    
    // Rutas de compras
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('show');
    });
    
    // Rutas de pagos
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/return', [PaymentController::class, 'return'])->name('return');
        Route::get('/stats', [PaymentController::class, 'stats'])->name('stats');
        Route::get('/success/{payment}', [PaymentController::class, 'success'])->name('success');
        Route::get('/failed/{payment}', [PaymentController::class, 'failed'])->name('failed');
        Route::post('/{payment}/retry', [PaymentController::class, 'retry'])->name('retry');
        Route::post('/{payment}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
        Route::get('/{payment}/receipt', [PaymentController::class, 'downloadReceipt'])->name('receipt');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    });
    

});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


