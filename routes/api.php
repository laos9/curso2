<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/avisos', [PostController::class, 'index']);
Route::get('/avisos/{post}', [PostController::class, 'show']);
Route::get('/resumen', [PostController::class, 'resumen']);

Route::post('/token', [TokenController::class, 'crear'])
    ->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
