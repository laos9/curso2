# Ejercicio 1 · Login mínimo: tu blog reconoce personas (18 min, en clase)

Tu blog ya lee y escribe avisos, pero cualquiera que llegue a `/avisos/crear` puede publicar. Antes de decidir **quién puede qué** (eso es el ejercicio 2), el sistema necesita saber **quién eres**. Eso es autenticación, y en Laravel son cuatro piezas: unos usuarios, un formulario, `Auth::attempt` y un candado en las rutas.

> No usamos ningún paquete ni instalador de plantillas: escribimos lo mínimo a mano para ver el mecanismo completo. Son unas 40 líneas.

## Parte 0 · Los usuarios de práctica (3 min)

La tabla `users` existe desde que nació tu proyecto (viene en el esqueleto de Laravel). Lo que llegó hoy con el pull es el `UserSeeder` y una migración que agrega la columna `rol`.

1. Registra el seeder en `database/seeders/DatabaseSeeder.php`, junto a los que ya tienes:
   ```php
   $this->call([
       CategoriaSeeder::class,
       PostSeeder::class,
       UserSeeder::class,
   ]);
   ```

2. Corre la migración nueva y siembra:
   ```bash
   php artisan migrate --seed
   ```

   ✅ **Checkpoint 0:** en `php artisan tinker`, `User::count()` te da 2 o más, y `User::first()->email` responde. Tus credenciales de práctica:
   - `admin@blog.test` / `secreto123` (rol admin)
   - `editor@blog.test` / `secreto123` (rol editor)

## Parte A · El controlador de sesión (7 min)

3. Genera el controlador:
   ```bash
   php artisan make:controller AuthController
   ```

4. Tres métodos: mostrar el formulario, entrar y salir.
   ```php
   <?php

   namespace App\Http\Controllers;

   use Illuminate\Http\Request;
   use Illuminate\Support\Facades\Auth;

   class AuthController extends Controller
   {
       public function mostrar()
       {
           return view('auth.login');
       }

       public function entrar(Request $request)
       {
           $datos = $request->validate([
               'email' => ['required', 'email'],
               'password' => ['required'],
           ]);

           if (Auth::attempt($datos)) {
               $request->session()->regenerate();

               return redirect()->intended('/');
           }

           return back()
               ->withErrors(['email' => 'Esas credenciales no coinciden con nuestros registros.'])
               ->onlyInput('email');
       }

       public function salir(Request $request)
       {
           Auth::logout();
           $request->session()->invalidate();
           $request->session()->regenerateToken();

           return redirect('/');
       }
   }
   ```
   Qué hace cada pieza:
   - **`Auth::attempt($datos)`** busca el usuario por correo y compara la contraseña **contra el hash** guardado (nunca se guarda en claro). Devuelve true o false.
   - **`session()->regenerate()`** cambia el identificador de sesión al entrar. Es la defensa estándar contra la fijación de sesión: sin esas dos líneas, el identificador que existía antes de entrar seguiría siendo válido.
   - **`redirect()->intended('/')`** te lleva a donde ibas antes de que te pidieran login.

5. Las rutas, en `routes/web.php`:
   ```php
   use App\Http\Controllers\AuthController;

   Route::get('/login', [AuthController::class, 'mostrar'])->name('login');
   Route::post('/login', [AuthController::class, 'entrar']);
   Route::post('/logout', [AuthController::class, 'salir'])->name('logout');
   ```
   > El nombre `login` no es decorativo: cuando alguien sin sesión toca una ruta protegida, Laravel lo manda a la ruta que se llama exactamente así.

## Parte B · La vista y el candado (8 min)

6. `resources/views/auth/login.blade.php`, con el mismo vocabulario de formularios que ya usas:
   ```blade
   @extends('layouts.publico')

   @section('titulo', 'Entrar')

   @section('contenido')
       <form method="POST" action="/login" class="max-w-sm mx-auto p-8 bg-white rounded-lg shadow mt-12">
           @csrf

           <h1 class="text-xl font-semibold text-gray-900 mb-4">Entrar al blog</h1>

           <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
           <input name="email" type="email" value="{{ old('email') }}"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
           @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

           <label class="block text-sm font-medium text-gray-700 mb-1 mt-4">Contraseña</label>
           <input name="password" type="password"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
           @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

           <button class="w-full bg-blue-900 text-white font-semibold rounded-lg py-2 hover:bg-blue-800 transition mt-5">
               Entrar
           </button>
       </form>
   @endsection
   ```

7. **El candado.** En `routes/web.php`, envuelve las rutas de escritura (crear, guardar, editar, actualizar, borrar) para que exijan sesión. La portada sigue pública:
   ```php
   Route::middleware('auth')->group(function () {
       Route::get('/avisos/crear', [PostController::class, 'create'])->name('avisos.create');
       Route::post('/avisos', [PostController::class, 'store'])->name('avisos.store');
       Route::get('/avisos/{post}/editar', [PostController::class, 'edit'])->name('avisos.edit');
       Route::put('/avisos/{post}', [PostController::class, 'update'])->name('avisos.update');
       Route::delete('/avisos/{post}', [PostController::class, 'destroy'])->name('avisos.destroy');
   });
   ```

8. En el layout `layouts/publico.blade.php`, una barra que diga quién eres (arriba del `@yield`):
   ```blade
   <div class="bg-white border-b px-6 py-2 text-sm flex justify-end items-center gap-4">
       @auth
           <span class="text-gray-600">Hola, {{ auth()->user()->name }}</span>
           <form method="POST" action="{{ route('logout') }}">
               @csrf
               <button class="text-blue-700 font-semibold hover:underline">Salir</button>
           </form>
       @else
           <a href="{{ route('login') }}" class="text-blue-700 font-semibold hover:underline">Entrar</a>
       @endauth
   </div>
   ```
   `@auth` y `@else` son directivas de Blade: muestran una cosa u otra según haya sesión.

   ✅ **Checkpoint B (todos):** en ventana normal ves "Entrar" y si tecleas `/avisos/crear` te manda al login. Entras con `admin@blog.test` / `secreto123`, la barra dice "Hola, Admin del blog" y ya puedes crear avisos. El botón Salir te regresa a visitante.

## Problemas comunes

| Síntoma | Causa probable |
|---|---|
| `Route [login] not defined` | La ruta GET `/login` no tiene `->name('login')`; el middleware la busca por ese nombre exacto |
| Entras y te regresa al formulario sin error | La contraseña del seeder no coincide: vuelve a correr `php artisan db:seed --class=UserSeeder` |
| `Undefined method 'attempt'` | Falta `use Illuminate\Support\Facades\Auth;` arriba del controlador |
| Después de entrar sigues viendo "Entrar" | El layout usa `@auth` pero el navegador cacheó la página: recarga con Ctrl+F5 |
| `SQLSTATE ... no such column: rol` | Te faltó `php artisan migrate` con la migración nueva que llegó en el pull |
