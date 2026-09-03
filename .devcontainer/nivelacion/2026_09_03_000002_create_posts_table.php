<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('contenido');
            // constrained() deduce la tabla desde el nombre de la columna:
            // categoria_id -> categorias, user_id -> users
            $table->foreignId('categoria_id')->constrained()->cascadeOnDelete();
            $table->boolean('publicado')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
