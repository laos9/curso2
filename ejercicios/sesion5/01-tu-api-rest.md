# Ejercicio 1 · Tu blog habla JSON (en clase + ~45 min en la semana)

Tu blog tiene tres caras hoy: la portada pública, el panel de Filament y, desde esta sesión, una API. Las tres leen la misma tabla, usan el mismo modelo y obedecen la misma Policy. Lo único que cambia es a quién le hablan: la portada le habla a una persona con navegador, el panel también, y la API le habla a **otro programa**.

> Requisitos: tu blog con `Post`, `Categoria`, el `PostController` de las sesiones 2 y 3 y la `PostPolicy`. Si te falta algo, `bash .devcontainer/nivelar-blog.sh` lo crea.

## Parte 0 · La carpeta que no existe (en clase, 5 min)

Abre `routes/`. Hay dos archivos: `console.php` y `web.php`. **No hay `api.php`.** Laravel 12 no lo trae: si tu proyecto no expone una API, ese archivo sería peso muerto.

```bash
php artisan install:api
```

Cuando pregunte si corre las migraciones, di que sí. El comando hace cuatro cosas:

1. Crea `routes/api.php`.
2. Agrega la línea `api: __DIR__.'/../routes/api.php'` en `bootstrap/app.php`. Ábrelo y búscala: es el mismo lugar donde en la sesión 3 se registran los middleware.
3. Instala **Sanctum** y publica `config/sanctum.php`.
4. Corre la migración `create_personal_access_tokens_table`: la tabla donde vivirán los tokens.

Fíjate en la última línea que imprime:

```
INFO  API scaffolding installed. Please add the [Laravel\Sanctum\HasApiTokens] trait to your User model.
```

Ese aviso es la tarea de la Parte 3. Déjalo pendiente por ahora.

**Lo que acabas de ganar:** todo lo que escribas en `routes/api.php` cuelga de `/api` sin que escribas el prefijo, y sale sin sesión ni cookies ni protección CSRF, porque quien va a llamarlo no es un navegador con formularios.

## Parte 1 · Las dos rutas públicas (en clase, 8 min)

En `routes/api.php`:

```php
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/avisos', [PostController::class, 'index']);
Route::get('/avisos/{post}', [PostController::class, 'show']);
```

Y el controlador:

```bash
php artisan make:controller Api/PostController
```

`Api/` con barra crea `app/Http/Controllers/Api/PostController.php`, en su propia carpeta. Tienes dos `PostController` y no chocan porque están en namespaces distintos.

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        return Post::publicados()->with('categoria')->latest()->paginate(10);
    }

    public function show(Post $post)
    {
        return $post;
    }
}
```

Es el `index()` de tu portada sin el `view()`. **Devolver un modelo o una colección basta:** Laravel los convierte a JSON solo.

Comprueba con `php artisan route:list --path=api` y abre `/api/avisos` en el navegador.

✅ **Checkpoint 1:** ves JSON con tus avisos. Fíjate en tres cosas que no escribiste: la llave `data`, la llave `links` y la llave `meta`. Eso lo agregó `paginate()`.

## Parte 2 · El API Resource, que decide qué sale (en clase, 12 min)

Mira bien el JSON de arriba. Están saliendo `created_at`, `updated_at`, `user_id` y `categoria_id` en crudo. Salen porque **devolver el modelo publica la tabla tal cual**, y la tabla es un detalle interno tuyo. El día que agregues una columna, aparece en la API sin que nadie lo decida.

```bash
php artisan make:resource PostResource
```

> Ya tienes un archivo llamado `PostResource.php`: el Resource de **Filament**, en `app/Filament/Resources/Posts/`. Este nuevo vive en `app/Http/Resources/` y es otra cosa. Mismo nombre, dos ideas distintas: el de Filament describe una pantalla de administración, este describe una respuesta JSON.

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'resumen' => $this->resumen,
            'contenido' => $this->contenido,
            'publicado' => $this->publicado,
            'categoria' => $this->whenLoaded('categoria', fn () => [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
            ]),
            'autor' => $this->whenLoaded('user', fn () => $this->user?->name),
            'creado' => $this->created_at->toIso8601String(),
        ];
    }
}
```

**La comparación que ordena todo esto:** este archivo es el `tarjeta-post.blade.php` de la API. Tu componente Blade decide qué ve el navegador; el API Resource decide qué ve otro programa. Mismo modelo, dos presentaciones.

Tres decisiones que están en ese código:

- **`whenLoaded`** solo incluye la relación si el controlador la cargó con `with()`. Sin eso, pintar la categoría de cada aviso dispararía una consulta por fila: el N+1 de la sesión 2, ahora en la API.
- **`autor`** manda el nombre, no el objeto `user` completo. Un `User` trae correo y contraseña hasheada; nada de eso tiene que salir.
- **`creado`** va en formato estándar (`2026-09-08T14:30:00+00:00`), no en `d/m/Y`. Quien consume decide cómo mostrar la fecha; tú no formateas para una pantalla que no conoces.

Ahora úsalo en el controlador:

```php
use App\Http\Resources\PostResource;

public function index()
{
    return PostResource::collection(
        Post::publicados()->with(['categoria', 'user'])->latest()->paginate(10)
    );
}

public function show(Post $post)
{
    return new PostResource($post->load(['categoria', 'user']));
}
```

✅ **Checkpoint 2:** recarga `/api/avisos`. Ya no salen `created_at` ni `user_id`, y la categoría viene como objeto con nombre.

## Parte 3 · El probador (en clase, 5 min)

El pull del curso te trajo `public/probador-api.html`. Ábrelo en tu propio Codespace:

```
https://<tu-codespace>-8000.app.github.dev/probador-api.html
```

Es una página que manda peticiones de verdad a tu API y te enseña el código de estado, la respuesta y el comando equivalente de terminal. La dirección se llena sola con la de tu proyecto.

> **Por qué vive dentro de tu proyecto y no en Moodle.** El navegador solo deja que una página llame a su propio origen, salvo que el servidor de destino lo permita. Tu Laravel sí lo permite, pero el túnel de Codespaces bloquea las llamadas de fuera antes de que lleguen. Desde tu proyecto, mismo origen, no hay problema. Si apuntas el probador a la API de un compañero, va a fallar en el navegador y va a funcionar con el comando de terminal: la restricción es del navegador, no de la API.

Prueba las dos rutas públicas. Luego prueba `GET /api/avisos/99999` y confirma que responde **404**.

## Parte 4 · Escribir por la API (en clase, 12 min)

Agrega al controlador:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

public function store(Request $request)
{
    Gate::authorize('create', Post::class);

    $datos = $request->validate([
        'titulo' => ['required', 'max:120'],
        'contenido' => ['required'],
        'categoria_id' => ['required', 'exists:categorias,id'],
    ]);

    $datos['user_id'] = $request->user()->id;

    $post = Post::create($datos);

    return (new PostResource($post->load(['categoria', 'user'])))
        ->response()
        ->setStatusCode(201);
}

public function update(Request $request, Post $post)
{
    Gate::authorize('update', $post);

    $datos = $request->validate([
        'titulo' => ['required', 'max:120'],
        'contenido' => ['required'],
        'categoria_id' => ['required', 'exists:categorias,id'],
    ]);

    $post->update($datos);

    return new PostResource($post->load(['categoria', 'user']));
}

public function destroy(Post $post)
{
    Gate::authorize('delete', $post);

    $post->delete();

    return response()->noContent();
}
```

Es tu `store()` de la sesión 3 con dos cambios: devuelve un Resource en vez de un `redirect()`, y **el código de estado es explícito**. `201` significa "se creó" y `204` significa "salió bien y no hay nada que devolver". Un cliente serio distingue las dos cosas de un `200` genérico.

La validación no cambia ni una línea, y sin embargo la respuesta es otra: en vez de regresar al formulario con `@error`, Laravel responde **422** con el detalle por campo. La misma regla, otra presentación.

Las rutas todavía no existen. Van en la Parte 1 de la guía 02, porque necesitan token.

## Nivel 1 · Filtrar por categoría (obligatorio)

Tu portada filtra con scopes. Tu tabla de Filament filtra con `SelectFilter`. Tu API filtra con parámetros de consulta:

```php
public function index(Request $request)
{
    $avisos = Post::publicados()
        ->with(['categoria', 'user'])
        ->when($request->categoria, fn ($q, $id) => $q->deCategoria($id))
        ->latest()
        ->paginate(10);

    return PostResource::collection($avisos);
}
```

Prueba `GET /api/avisos?categoria=2`. Es tu scope `deCategoria()` de la sesión 2, ahora accesible desde fuera.

## Nivel 2 · Un endpoint que no es un CRUD (obligatorio, en la semana)

No toda ruta de API corresponde a una tabla. Agrega una que responda un resumen:

```php
// routes/api.php
Route::get('/resumen', [PostController::class, 'resumen']);

// Api/PostController.php
public function resumen()
{
    return [
        'total' => Post::count(),
        'publicados' => Post::publicados()->count(),
        'borradores' => Post::where('publicado', false)->count(),
        'por_categoria' => Categoria::withCount('posts')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->nombre => $c->posts_count]),
    ];
}
```

Son los mismos números de tu widget de Filament, servidos como datos en vez de como tarjetas.

## Nivel 3 · Opcional, cuenta como extra

**Opción A:** que `show` acepte también el slug además del id, con `Route::get('/avisos/{post:slug}')`, si tu tabla tiene esa columna.

**Opción B:** un `EtiquetaResource` y que cada aviso incluya sus etiquetas con `whenLoaded('etiquetas')`, si hiciste el nivel avanzado de la sesión 2.

## Si algo falla

| Lo que ves | Qué es | Qué hacer |
|---|---|---|
| `Route [login] not defined` o te devuelve HTML del login | Tu petición no dijo que quería JSON | Manda el encabezado `Accept: application/json`. El probador lo hace siempre |
| `404` en `/api/avisos` cuando la ruta sí existe | Escribiste `/avisos` en `api.php` y estás probando sin el prefijo | Las rutas de `api.php` viven bajo `/api` |
| `Class "App\Http\Resources\PostResource" not found` | El `use` apunta al Resource de Filament | Revisa el `use` del controlador: `App\Http\Resources\PostResource` |
| El JSON trae `created_at` y `user_id` todavía | El controlador devuelve el modelo, no el Resource | Envuélvelo: `new PostResource($post)` |
| `Call to a member function id() on null` en `store` | `$request->user()` es nulo porque la ruta no exige token | Es la Parte 1 de la guía 02 |
| El probador dice "no se pudo conectar" | El servidor no está corriendo, o apuntaste a otro Codespace | `composer run dev`, y revisa la dirección de arriba |
