<?php
use Illuminate\Support\Facades\Route;
use App\Models\Post;

use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\PostController as ApiPostController;

Route::get('/', [PostController::class, 'index'])->name('avisos.index');
Route::middleware('auth')->group(function () {
    Route::get('/avisos/crear', [PostController::class, 'create'])->name('avisos.create');
    Route::post('/avisos', [PostController::class, 'store'])->name('avisos.store');
    Route::get('/avisos/{post}/editar', [PostController::class, 'edit'])->name('avisos.edit');
    Route::put('/avisos/{post}', [PostController::class, 'update'])->name('avisos.update');
    Route::delete('/avisos/{post}', [PostController::class, 'destroy'])->name('avisos.destroy');
});

Route::get('/login', [AuthController::class, 'mostrar'])->name('login');
Route::post('/login', [AuthController::class, 'entrar']);
Route::post('/logout', [AuthController::class, 'salir'])->name('logout');
Route::get('/avisos', [PostController::class, 'index']);
Route::get('/avisos/{post}', [PostController::class, 'show']);





