<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {return Inertia::render('LayoutMaster');})->name('home');

Route::get('dashboard', function () {return Inertia::render('administration/Dashboard');})->middleware(['auth', 'verified'])->name('dashboard');
Route::get('Formulario', function () {return Inertia::render('clients/DinamycForm');})->middleware(['auth', 'verified'])->name('Formulario');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
