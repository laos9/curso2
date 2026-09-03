<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Un rol simple para el blog: 'admin' puede con todo, 'editor' solo con lo suyo.
// Es a proposito una columna de texto y no un paquete de permisos: el objetivo
// de la sesion es entender el mecanismo, no configurar una libreria.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('editor');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }
};
