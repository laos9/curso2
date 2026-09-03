<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Usuarios de practica del blog. Con estos entras al sistema en clase.
 *
 *   admin@blog.test   / secreto123   (rol admin)
 *   editor@blog.test  / secreto123   (rol editor)
 *
 * Es idempotente: puedes correrlo las veces que quieras.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'admin@blog.test']);
        $admin->name = 'Admin del blog';
        $admin->password = Hash::make('secreto123');
        if (Schema::hasColumn('users', 'rol')) {
            $admin->rol = 'admin';
        }
        $admin->save();

        $editor = User::firstOrNew(['email' => 'editor@blog.test']);
        $editor->name = 'Editor de guardia';
        $editor->password = Hash::make('secreto123');
        if (Schema::hasColumn('users', 'rol')) {
            $editor->rol = 'editor';
        }
        $editor->save();

        // Si los avisos ya tienen dueno (columna user_id del ejercicio 2),
        // los que vengan sin dueno quedan a nombre del admin.
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'user_id') && class_exists(\App\Models\Post::class)) {
            \App\Models\Post::whereNull('user_id')->update(['user_id' => $admin->id]);
        }
    }
}
