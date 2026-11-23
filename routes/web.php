<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GarageController;
use Illuminate\Support\Facades\Route;

//Público
Route::get('/', [GarageController::class, 'index'])->name('garages.index');
Route::get('/api/garages/cercanas', [GarageController::class, 'cercanas'])->name('garages.cercanas');

//Solo visitantes sin sesión
Route::middleware('guest')->group(function () {
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // frena fuerza bruta
});

//Solo usuarios con sesión
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/mis-cocheras', [GarageController::class, 'mias'])->name('garages.mias');
    Route::get('/garages/create', [GarageController::class, 'create'])->name('garages.create');
    Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');

    // Estas tres además pasan por la Policy: hay que ser el DUEÑO de la cochera.
    Route::get('/garages/{garage}/edit', [GarageController::class, 'edit'])->name('garages.edit');
    Route::put('/garages/{garage}', [GarageController::class, 'update'])->name('garages.update');
    Route::delete('/garages/{garage}', [GarageController::class, 'destroy'])->name('garages.destroy');
});

// Va a ir después de /garages/create para que "create" no se interprete como un {garage}.
Route::get('/garages/{garage}', [GarageController::class, 'show'])->name('garages.show');
