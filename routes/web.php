<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PostController;

// SECCION DE INVITADOS O CLIENTES
Route::redirect('/', '/inicio');
Route::get('/inicio', function () {return Inertia::render('LayoutMaster');})-> name('inicio');
Route::get('publicaciones', function () {return Inertia::render('ClientMenu/Post');})-> name('publicaciones');
Route::get('evaluacion', function () {return Inertia::render('ClientMenu/Evaluation');})-> name('evaluacion');



Route::get('dashboard', function () {return Inertia::render('administration/Dashboard');})->middleware(['auth', 'verified'])->name('dashboard');



// Rutas para Posts
Route::resource('posts', PostController::class);
Route::patch('posts/{post}/status', [PostController::class, 'changeStatus'])->name('posts.change-status');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


