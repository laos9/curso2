# Ejercicio 5 · El CRUD de avisos con su controlador (~50 min, en la tarea)

El formulario del Ejercicio 1 es decorativo y la portada solo lee. Aquí el blog completa el ciclo: **crear, editar y borrar avisos**, con validación real. Y una regla de arquitectura que va en serio desde hoy:

> **`routes/web.php` solo ENRUTA: cero lógica ahí.** Los closures que usamos en clase sirven para demos de cinco minutos; el código real vive en **controladores**. Hay sistemas en producción con miles de líneas de lógica metidas en el archivo de rutas, y mantenerlos duele todos los días. Tu blog no va a ser uno de esos.

> Requisitos: Ejercicio 1 (formulario estilizado) y niveles 1-2 del Ejercicio 2 (portada con BD y scope `publicados`). Es parte obligatoria de la tarea.

> **Si trabajas en GitHub Codespaces**, el pull de esta sesión te trae ya resuelto `app/Providers/AppServiceProvider.php` y no tienes que hacer nada. Sin ese arreglo, guardas un aviso y el navegador se queda en una página de error: tú entras por `https://<tu-codespace>-8000.app.github.dev`, pero la petición cruza el túnel de Codespaces y a Laravel le llega con otro host y otro puerto. Como `route()`, `asset()` y los `redirect()` se arman con lo que trae la petición, el `redirect()` de tu `store()` termina apuntando a `http://localhost:8000/`, que en tu navegador no existe. Ábrelo si quieres ver cómo se corrige: tiene un detalle que sorprende, porque `php artisan serve` atiende las peticiones en un **proceso aparte** al que no le pasa todas las variables de entorno.

## Parte 0 · Nace el controlador y la portada se muda (10 min)

1. Genera el controlador **con todo el esqueleto del CRUD ya puesto**:
   ```bash
   php artisan make:controller PostController --resource --model=Post
   ```
   Ábrelo: no está vacío. Trae los **siete métodos** que forman el mapa estándar de un CRUD en Laravel, y ese mapa es el mismo en cualquier proyecto del mundo:

   | Método | Para qué sirve | ¿Lo llenamos hoy? |
   |---|---|---|
   | `index` | la lista (tu portada) | sí |
   | `create` | mostrar el formulario de alta | sí |
   | `store` | guardar lo que llegó del formulario | sí |
   | `show` | la página de UN aviso | no, se queda vacío |
   | `edit` | mostrar el formulario de edición | sí |
   | `update` | guardar los cambios | sí |
   | `destroy` | borrar | sí |

   Las dos banderas importan: `--resource` crea los siete métodos, y `--model=Post` hace que los que trabajan sobre un aviso ya reciban el modelo listo (`edit(Post $post)`). Eso es **route model binding**: Laravel recibe el id de la URL, busca el aviso y te lo entrega, o responde 404 si no existe. Nunca escribes `Post::find($id)`.

   > Tu trabajo de aquí en adelante es **llenar cuerpos**, no escribir firmas.

2. La portada deja el closure y se muda al método `index`, que ya existe. Complétalo (y agrega los `use` de arriba si no están):
   ```php
   use App\Models\Categoria;
   use App\Models\Post;
   use Illuminate\Http\Request;

   public function index()
   {
       $posts = Post::publicados()->with('categoria')->latest()->get();

       return view('portada', ['posts' => $posts]);
   }
   ```

3. Y `routes/web.php` queda SOLO enrutando (borra el closure de `/`):
   ```php
   use App\Http\Controllers\PostController;

   Route::get('/', [PostController::class, 'index'])->name('avisos.index');
   ```

   Ese `publicados()` es el **scope** que definiste en el nivel 2 (Ejercicio 2, paso 9): la consulta con nombre del modelo. Si todavía no haces el nivel 2, usa `Post::with('categoria')->latest()->get()` mientras tanto y regresa a ponerle el scope después.

   ✅ **Checkpoint 0:** la portada se ve idéntica, pero la lógica ya vive en el controlador. (Error típico: `Target class [PostController] does not exist` = te faltó el `use` de arriba. Y `Call to undefined method publicados()` = el scope del nivel 2 aún no existe en tu modelo.)

## Parte A · Crear avisos: create + store (15 min)

4. Dos rutas más (el patrón: GET muestra el formulario, POST guarda):
   ```php
   Route::get('/avisos/crear', [PostController::class, 'create'])->name('avisos.create');
   Route::post('/avisos', [PostController::class, 'store'])->name('avisos.store');
   ```

5. Llena los dos métodos, que ya existen en el archivo:
   ```php
   public function create()
   {
       return view('avisos.crear', ['categorias' => Categoria::orderBy('nombre')->get()]);
   }

   public function store(Request $request)
   {
       $datos = $request->validate([
           'titulo' => ['required', 'max:120'],
           'contenido' => ['required'],
           'categoria_id' => ['required', 'exists:categorias,id'],
       ]);

       Post::create($datos);

       return redirect()->route('avisos.index');
   }
   ```
   Qué hace `validate()`: si algo falla, regresa al formulario con los mensajes listos para `@error` y lo escrito en `old()`, sin ejecutar lo demás; si pasa, entrega SOLO los campos validados (por eso `$datos` va directo a `create`).

6. La vista `resources/views/avisos/crear.blade.php`, con el vocabulario del Ejercicio 1:
   ```blade
   @extends('layouts.publico')

   @section('titulo', 'Nuevo aviso')

   @section('contenido')
       <form method="POST" action="{{ route('avisos.store') }}" class="max-w-lg mx-auto p-8 bg-white rounded-lg shadow mt-8">
           @csrf

           <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
           <input name="titulo" value="{{ old('titulo') }}"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
           @error('titulo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

           <label class="block text-sm font-medium text-gray-700 mb-1 mt-4">Categoría</label>
           <select name="categoria_id"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
               <option value="">Elige una categoría...</option>
               @foreach ($categorias as $categoria)
                   <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>
                       {{ $categoria->nombre }}
                   </option>
               @endforeach
           </select>
           @error('categoria_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

           <label class="block text-sm font-medium text-gray-700 mb-1 mt-4">Contenido</label>
           <textarea name="contenido" rows="4"
                     class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">{{ old('contenido') }}</textarea>
           @error('contenido') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

           <button class="w-full bg-blue-900 text-white font-semibold rounded-lg py-2 hover:bg-blue-800 transition mt-5">
               Publicar aviso
           </button>
       </form>
   @endsection
   ```
   Las tres piezas clave: **`@csrf`** (token de seguridad de todo POST; sin él, error 419), **`old('campo')`** (si la validación falla, lo escrito no se pierde) y **`@error('campo')`** (las clases de estado de error del Ejercicio 1, conectadas de verdad).

7. La puerta de entrada, en el hero de la portada:
   ```blade
   <a href="{{ route('avisos.create') }}"
      class="inline-block mt-4 bg-white text-blue-950 font-semibold rounded-lg px-4 py-2 hover:bg-blue-100 transition">
       Nuevo aviso
   </a>
   ```

   ✅ **Checkpoint A:** enviar vacío regresa con mensajes en rojo y no crea nada; lleno, redirige a la portada y tu aviso aparece hasta arriba.

## Parte B · Editar avisos: edit + update (15 min)

8. Dos rutas más. El `{post}` es **route model binding**: Laravel recibe el id en la URL y te entrega el modelo ya cargado (o un 404 si no existe):
   ```php
   Route::get('/avisos/{post}/editar', [PostController::class, 'edit'])->name('avisos.edit');
   Route::put('/avisos/{post}', [PostController::class, 'update'])->name('avisos.update');
   ```

9. Llena los dos métodos (fíjate que `edit` y `update` ya reciben el `Post $post` gracias a `--model`):
   ```php
   public function edit(Post $post)
   {
       return view('avisos.editar', [
           'post' => $post,
           'categorias' => Categoria::orderBy('nombre')->get(),
       ]);
   }

   public function update(Request $request, Post $post)
   {
       $datos = $request->validate([
           'titulo' => ['required', 'max:120'],
           'contenido' => ['required'],
           'categoria_id' => ['required', 'exists:categorias,id'],
       ]);

       $post->update($datos);

       return redirect()->route('avisos.index');
   }
   ```

10. La vista `avisos/editar.blade.php` es `crear.blade.php` duplicada con TRES cambios:
    - El form: `action="{{ route('avisos.update', $post) }}"` y, justo bajo el `@csrf`, la línea `@method('PUT')` (los formularios HTML solo saben GET y POST; esa directiva le dice a Laravel el verbo real).
    - Los valores arrancan con lo guardado: `old('titulo', $post->titulo)`, `old('contenido', $post->contenido)` y en el select `@selected(old('categoria_id', $post->categoria_id) == $categoria->id)`. Así: primera visita muestra el aviso, validación fallida muestra lo que tecleaste.
    - El botón dice "Guardar cambios".

11. El enlace en cada tarjeta (`<x-tarjeta-post>`):
    ```blade
    <a href="{{ route('avisos.edit', $post) }}" class="text-blue-700 text-sm font-semibold hover:underline mt-4 inline-block">Editar</a>
    ```

    ✅ **Checkpoint B:** entras a editar y el formulario llega LLENO; cambias el título, guardas, y la portada lo muestra actualizado.

## Parte C · Borrar avisos: destroy (10 min)

12. La ruta y el método `destroy`, que también viene listo para recibir el aviso:
    ```php
    Route::delete('/avisos/{post}', [PostController::class, 'destroy'])->name('avisos.destroy');
    ```
    ```php
    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('avisos.index');
    }
    ```

13. El botón en la tarjeta va dentro de su propio mini formulario (borrar NUNCA se hace con un enlace GET):
    ```blade
    <form method="POST" action="{{ route('avisos.destroy', $post) }}" class="inline"
          onsubmit="return confirm('¿Borrar este aviso?')">
        @csrf
        @method('DELETE')
        <button class="text-red-600 text-sm font-semibold hover:underline">Borrar</button>
    </form>
    ```

    ✅ **Checkpoint C:** borras un aviso y desaparece de la portada. Si hiciste el nivel 3 (soft deletes), en Tinker comprueba que sigue vivo: `Post::onlyTrashed()->count()`.

## 🔥 Desafío (si quieres ir más lejos)

- **Mensaje de éxito:** `return redirect()->route('avisos.index')->with('ok', 'Aviso guardado');` y en la portada: `@if (session('ok')) <p class="max-w-4xl mx-auto mt-4 bg-green-100 text-green-800 rounded-lg px-4 py-2">{{ session('ok') }}</p> @endif`.
- **Menos rutas escritas a mano:** `Route::resource('avisos', PostController::class)` genera el juego completo con nombres estándar; hoy las escribimos una por una para ver el mapa.
- **Formulario sin duplicar:** extrae los campos a un parcial (`@include('avisos._campos')`) o al componente `<x-campo>` del Ejercicio 1.

## Problemas comunes

| Síntoma | Causa probable |
|---|---|
| `Target class [PostController] does not exist` | Falta `use App\Http\Controllers\PostController;` arriba de `routes/web.php` |
| El controlador salió vacío, sin métodos | Te faltaron las banderas: `--resource --model=Post` |
| `419 Page Expired` al enviar | Falta `@csrf` dentro del `<form>` |
| `MethodNotAllowedHttpException` al guardar la edición | Falta `@method('PUT')` en el form de editar (o `@method('DELETE')` en el de borrar) |
| `Add [titulo] to fillable property` | El `$fillable` del modelo `Post` no incluye ese campo (Ejercicio 2, paso 3) |
| El formulario de editar llega vacío | Usaste `old('titulo')` sin el segundo argumento `$post->titulo` |
| 404 al editar o borrar | El `{post}` de la URL no existe en la tabla (route model binding responde 404 solo) |
| `Post::create` guarda pero la portada no lo muestra | Con el nivel 2, la portada solo muestra publicados; `publicado` nace en `true` por el default de la migración: revisa que la migración tenga el `->default(true)` |
