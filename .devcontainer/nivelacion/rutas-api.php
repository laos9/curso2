<?php

// Solucion de la sesion 5: routes/api.php
// Este archivo NO existe en Laravel 12 de fabrica. Lo crea `php artisan install:api`,
// que ademas instala Sanctum y agrega la linea `api:` en bootstrap/app.php.
// Todo lo que se declare aqui cuelga de /api automaticamente.

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Publico: leer avisos, como tu portada pero en JSON.
Route::get('/avisos', [PostController::class, 'index']);
Route::get('/avisos/{post}', [PostController::class, 'show']);

// Entregar un token a quien traiga credenciales correctas.
Route::post('/token', [TokenController::class, 'crear']);

// Con token: escribir, y saber quien eres.
Route::middleware('auth:sanctum')->group(function () {
    // Devolver $request->user() en crudo publicaria la tabla users. Se elige
    // que sale, igual que en el PostResource.
    Route::get('/yo', fn (Request $request) => [
        'id' => $request->user()->id,
        'nombre' => $request->user()->name,
        'rol' => $request->user()->rol,
    ]);
    Route::post('/avisos', [PostController::class, 'store']);
    Route::put('/avisos/{post}', [PostController::class, 'update']);
    Route::delete('/avisos/{post}', [PostController::class, 'destroy']);
    Route::post('/token/revocar', [TokenController::class, 'revocar']);
});
