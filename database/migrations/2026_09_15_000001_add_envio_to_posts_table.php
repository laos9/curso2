<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sesion 6, bloque de colas: el seguimiento del envio de cada aviso.
// `destinatarios` es a cuantos usuarios hay que mandarlo y `notificados` a
// cuantos ya les llego. El trabajo EnviarAvisoPorCorreo las llena y tu API las
// muestra: asi el avance del trabajo en segundo plano se ve desde fuera.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('destinatarios')->default(0);
            $table->unsignedInteger('notificados')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['destinatarios', 'notificados']);
        });
    }
};
