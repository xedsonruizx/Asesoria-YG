<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
Route::redirect('/', '/inicio');
Route::get('/inicio', function () {return Inertia::render('LayoutMaster');})->name('inicio');

Route::get('dashboard', function () {return Inertia::render('administration/Dashboard');})->middleware(['auth', 'verified'])->name('dashboard');
Route::get('publicaciones', function () {return Inertia::render('clients/Post');})->name('publicaciones');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


