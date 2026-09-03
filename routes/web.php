<?php
use Illuminate\Support\Facades\Route;
use App\Models\Post;

use App\Http\Controllers\PostController;

Route::get('/', [PostController::class, 'index'])->name('avisos.index');
Route::get('/avisos/crear', [PostController::class, 'create'])->name('avisos.create');
Route::post('/avisos', [PostController::class, 'store'])->name('avisos.store');
Route::get('/avisos/{post}/editar', [PostController::class, 'edit'])->name('avisos.edit');
Route::put('/avisos/{post}', [PostController::class, 'update'])->name('avisos.update');
Route::delete('/avisos/{post}', [PostController::class, 'destroy'])->name('avisos.destroy');






