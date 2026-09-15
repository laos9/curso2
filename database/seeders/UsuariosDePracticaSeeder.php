<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Usuarios de practica para el bloque de colas de la sesion 6. A mas
 * destinatarios, mas tarda mandarle el aviso a todos, y mejor se ve la cola.
 *
 *   php artisan db:seed --class=UsuariosDePracticaSeeder
 *
 * Es idempotente: si ya existen, no los duplica.
 */
class UsuariosDePracticaSeeder extends Seeder
{
    public function run(): void
    {
        $nombres = ['Ana Ruiz', 'Luis Mora', 'Marta Salas', 'Jorge Pena', 'Sofia Vega', 'Raul Campos', 'Elena Rios', 'Tomas Ibarra'];

        foreach ($nombres as $i => $nombre) {
            $usuario = User::firstOrNew(['email' => 'practica'.($i + 1).'@blog.test']);
            $usuario->name = $nombre;
            $usuario->password = Hash::make('secreto123');
            if (Schema::hasColumn('users', 'rol')) {
                $usuario->rol = 'lector';
            }
            $usuario->save();
        }
    }
}
