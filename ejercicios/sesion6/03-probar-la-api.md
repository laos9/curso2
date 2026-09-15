# Ejercicio 3 · Las pruebas de tu API

Objetivo: escribir la suite que comprueba lo que hiciste a mano en la sesión 5. Al terminar, `php artisan test` te dice en un solo comando si tu API sigue respondiendo 200, 401, 201, 422 y 403 donde debe.

Tiempo estimado: 30 minutos en clase, el resto en la tarea.

Requisito: haber terminado el ejercicio 2 (Pest instalado, factories creadas, una prueba en verde).

---

## Cómo se organiza el archivo

Pest deja agrupar pruebas con `describe`, y correr algo antes de cada una con `beforeEach`. Así queda la cabecera de `tests/Feature/Api/AvisosApiTest.php`:

```php
<?php

use App\Jobs\EnviarAvisoPorCorreo;
use App\Models\Categoria;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    $this->categoria = Categoria::factory()->create(['nombre' => 'Avisos generales']);
    $this->editor = User::factory()->create(['rol' => 'editor']);
});
```

`beforeEach` corre antes de **cada** prueba, y como la base se limpia entre pruebas, esa categoría y ese usuario se vuelven a crear cada vez. Lo que guardas en `$this->` queda disponible dentro de las pruebas.

**`Queue::fake()` es por el ejercicio 1.** Tu `store()` ahora despacha el correo a la cola, y en las pruebas las colas corren en modo `sync` (lo dice tu `phpunit.xml`): sin esa línea, cada aviso que crea una prueba ejecutaría el trabajo de verdad, con su espera por cada correo. `Queue::fake()` guarda los trabajos en vez de ejecutarlos, y te deja preguntar después si se despacharon.

---

## Paso 1 · Leer no necesita token

```php
describe('leer avisos, sin token', function () {

    test('el listado responde 200 y trae solo los publicados', function () {
        Post::factory()->count(3)->create(['categoria_id' => $this->categoria->id, 'publicado' => true]);
        Post::factory()->create(['categoria_id' => $this->categoria->id, 'publicado' => false]);

        $this->getJson('/api/avisos')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('cada aviso trae los campos que decidio el PostResource', function () {
        Post::factory()->create(['categoria_id' => $this->categoria->id, 'titulo' => 'Cambio de horario']);

        $this->getJson('/api/avisos')
            ->assertJsonPath('data.0.titulo', 'Cambio de horario')
            ->assertJsonPath('data.0.categoria.nombre', 'Avisos generales');
    });
});
```

La segunda prueba es más interesante de lo que parece: está comprobando tu `PostResource`, o sea la decisión de qué campos salen y con qué nombre. Si mañana alguien renombra `titulo` a `title` en el Resource, esta prueba se pone roja antes de que se entere quien consume tu API.

---

## Paso 2 · Escribir sí necesita token

```php
describe('escribir avisos', function () {

    test('sin token responde 401', function () {
        $this->postJson('/api/avisos', ['titulo' => 'x'])->assertStatus(401);
    });

    test('con token crea el aviso y responde 201', function () {
        Sanctum::actingAs($this->editor);

        $this->postJson('/api/avisos', [
            'titulo' => 'Capacitacion el viernes',
            'contenido' => 'A las 9 en la sala 2.',
            'categoria_id' => $this->categoria->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('posts', [
            'titulo' => 'Capacitacion el viernes',
            'user_id' => $this->editor->id,
        ]);

        Queue::assertPushed(EnviarAvisoPorCorreo::class,
            fn ($trabajo) => $trabajo->post->titulo === 'Capacitacion el viernes');
    });

    test('sin titulo responde 422 y dice cual campo fallo', function () {
        Sanctum::actingAs($this->editor);

        $this->postJson('/api/avisos', ['contenido' => 'sin titulo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['titulo', 'categoria_id']);
    });
});
```

Cuatro notas:

- `Sanctum::actingAs($usuario)` sustituye a pedir un token real. A partir de esa línea, las peticiones de esa prueba van identificadas.
- La prueba del 201 no se conforma con el código: **también comprueba la base de datos**, y comprueba que el `user_id` sea el del que mandó la petición, no cualquiera.
- `assertJsonValidationErrors` mira dentro del 422 y verifica de qué campos se quejó. Un 422 por el campo equivocado no es lo mismo que el 422 que esperabas.
- `Queue::assertPushed` comprueba que el correo **quedó en la cola**, y con el aviso correcto. Si alguien quita el `dispatch()` del `store()`, esta prueba se pone roja.

---

## Paso 3 · La Policy de la sesión 3, ahora medida

Esta es la parte que más vale la pena de todo el archivo. Escribiste esa Policy hace tres semanas y hoy gobierna el blog, el panel y la API. Estas pruebas la fijan.

```php
describe('la Policy de la sesion 3, ahora en la API', function () {

    test('editar un aviso ajeno responde 403', function () {
        $ajeno = Post::factory()->create([
            'categoria_id' => $this->categoria->id,
            'user_id' => User::factory()->create(['rol' => 'editor'])->id,
        ]);

        Sanctum::actingAs($this->editor);

        $this->putJson("/api/avisos/{$ajeno->id}", [
            'titulo' => 'Secuestrado',
            'contenido' => 'mio ahora',
            'categoria_id' => $this->categoria->id,
        ])->assertStatus(403);

        expect($ajeno->fresh()->titulo)->not->toBe('Secuestrado');
    });

    test('editar el propio responde 200', function () {
        $mio = Post::factory()->create([
            'categoria_id' => $this->categoria->id,
            'user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $this->putJson("/api/avisos/{$mio->id}", [
            'titulo' => 'Corregido',
            'contenido' => 'ya quedo',
            'categoria_id' => $this->categoria->id,
        ])->assertStatus(200)->assertJsonPath('data.titulo', 'Corregido');
    });

    test('un admin si puede con el ajeno', function () {
        $ajeno = Post::factory()->create(['categoria_id' => $this->categoria->id]);
        Sanctum::actingAs(User::factory()->create(['rol' => 'admin']));

        $this->deleteJson("/api/avisos/{$ajeno->id}")->assertStatus(204);
        $this->assertDatabaseMissing('posts', ['id' => $ajeno->id]);
    });
});
```

Fíjate en la línea `expect($ajeno->fresh()->titulo)->not->toBe('Secuestrado');`. El 403 dice que la aplicación **respondió** que no. Esa línea comprueba que además **no lo hizo**. Son dos cosas distintas y las dos importan: un sistema puede responder 403 y haber guardado el cambio de todos modos.

La tercera prueba mide el `before()` de tu Policy, ese que devuelve `true` para admin. Sin prueba, ese método es el más fácil de romper sin darse cuenta.

---

## Paso 4 · La misma regla, sin HTTP: una prueba unitaria

Todo lo anterior son pruebas **de integración**: atraviesan la aplicación completa. Laravel les dice *Feature* y por eso viven en `tests/Feature`. Ahora una **unitaria**, que prueba una pieza sola.

La pieza ideal es tu `PostPolicy`: recibe un usuario y un aviso y devuelve sí o no. No necesita rutas, ni controlador, ni base de datos.

```bash
php artisan make:test PostPolicyTest --unit --pest
```

`tests/Unit/PostPolicyTest.php`:

```php
<?php

use App\Models\Post;
use App\Models\User;
use App\Policies\PostPolicy;

beforeEach(function () {
    $this->policy = new PostPolicy();
});

test('el dueno puede editar su aviso', function () {
    $yo = (new User)->forceFill(['id' => 1, 'rol' => 'editor']);
    $mio = (new Post)->forceFill(['user_id' => 1]);

    expect($this->policy->update($yo, $mio))->toBeTrue();
});

test('un editor no puede editar el aviso de otro', function () {
    $yo = (new User)->forceFill(['id' => 1, 'rol' => 'editor']);
    $ajeno = (new Post)->forceFill(['user_id' => 2]);

    expect($this->policy->update($yo, $ajeno))->toBeFalse();
});

test('before() deja pasar al admin', function () {
    $admin = (new User)->forceFill(['id' => 1, 'rol' => 'admin']);

    expect($this->policy->before($admin, 'update'))->toBeTrue();
});

test('before() no opina sobre un editor', function () {
    $editor = (new User)->forceFill(['id' => 1, 'rol' => 'editor']);

    expect($this->policy->before($editor, 'update'))->toBeNull();
});

test('un lector no puede crear avisos', function () {
    $lector = (new User)->forceFill(['id' => 1, 'rol' => 'lector']);

    expect($this->policy->create($lector))->toBeFalse();
});
```

Tres cosas distintas de lo que venías haciendo:

- **No hay `uses(RefreshDatabase::class)`.** Los modelos se arman en memoria y nunca se guardan, así que no hace falta base de datos. Por eso cada una tarda una centésima de segundo.
- **No hay `$this->getJson()`.** Llamas al método de la Policy directo, como a cualquier función.
- **`forceFill` y no `new User([...])`.** `rol` no está en el `$fillable` de `User`, así que `new User(['rol' => 'admin'])` lo tira en silencio y la prueba falla con `Failed asserting that null is true.` Es la asignación masiva de la sesión 5, apareciendo por otro lado. `forceFill` se salta esa protección, y en una prueba está bien porque los datos los pones tú.

### Para qué sirven las dos

Haz este experimento: quita el `Gate::authorize('update', $post);` del `update()` de tu `Api/PostController` y corre todo.

| Prueba | Resultado |
|---|---|
| Las cinco unitarias de la Policy | **en verde** |
| "editar un aviso ajeno responde 403" | **en rojo** |

La regla sigue bien escrita, por eso la unitaria no se entera. Lo que se rompió es que **nadie la llama**, y eso solo lo ve la de integración. Al revés también pasa: si cambias la Policy para que `update()` devuelva `true`, caen las dos.

La unitaria te dice que la regla está bien. La de integración te dice que alguien la está usando. Regresa el `Gate::authorize` antes de seguir.

---

## Paso 5 · Las pruebas de token

```php
describe('tokens', function () {

    test('credenciales correctas devuelven un token', function () {
        $this->postJson('/api/token', [
            'email' => $this->editor->email,
            'password' => 'password',
            'dispositivo' => 'pruebas',
        ])->assertStatus(200)->assertJsonStructure(['token', 'usuario', 'rol']);

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'pruebas']);
    });

    test('credenciales incorrectas responden 422 y no dejan token', function () {
        $this->postJson('/api/token', [
            'email' => $this->editor->email,
            'password' => 'la-que-no-es',
            'dispositivo' => 'pruebas',
        ])->assertStatus(422);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });
});
```

El `'password' => 'password'` no es un descuido: la `UserFactory` de Laravel crea todos sus usuarios con esa contraseña. Por eso funciona sin que tú la hayas puesto.

La segunda prueba tiene el remate en la última línea: no basta con que responda 422, hay que comprobar que **no se emitió un token**. Un endpoint de login que rechaza y de todos modos deja credencial es exactamente el tipo de agujero del que hablamos en la sesión 5.

---

## Paso 6 · La prueba que cuida tu JSON

En la sesión 5 quedó dicho que devolver un modelo en crudo publica tu tabla. `GET /api/yo` devuelve solo tres campos elegidos. Esto lo fija:

```php
test('GET /api/yo no publica la tabla users', function () {
    Sanctum::actingAs($this->editor);

    $respuesta = $this->getJson('/api/yo')->assertStatus(200);

    $respuesta->assertJsonPath('nombre', $this->editor->name);
    expect(array_keys($respuesta->json()))->toBe(['id', 'nombre', 'rol']);
});
```

La última línea es la importante: no dice "que traiga el nombre", dice **"que traiga exactamente estas tres llaves y ninguna más"**. Si alguien mañana cambia esa ruta por un `return $request->user();`, la prueba se pone roja porque aparecerían `email`, `created_at` y lo demás.

Esa es una prueba que no comprueba que algo funcione, sino que algo **no se filtre**. Vale por diez.

---

## Checkpoint

```bash
php artisan test
```

Deberías ver alrededor de dieciséis pruebas en verde (once de integración y cinco unitarias), y la suite completa en un par de segundos o menos. La primera que usa la base tarda un poco más porque monta el esquema en memoria; las demás van en centésimas.

Comprueba:

1. Las cinco respuestas de tu API tienen prueba: 200, 401, 201, 422 y 403.
2. Al menos una prueba mira la base de datos, no solo el código de estado.
3. Al menos una prueba comprueba que algo **no** pasó.
4. La regla de tu Policy tiene sus pruebas unitarias en `tests/Unit/PostPolicyTest.php`.

---

## Extra · la prueba de suplantación

Para quien vaya adelantado. En el `store()` de tu controlador, el `user_id` se pone después de validar:

```php
$datos = $request->validate([...]);
$datos['user_id'] = $request->user()->id;
```

¿Qué pasa si alguien manda `user_id` de otra persona en el JSON? Escríbelo como prueba en vez de razonarlo:

```php
test('mandar user_id de otro no cambia el dueno del aviso', function () {
    $otro = User::factory()->create();

    Sanctum::actingAs($this->editor);

    $this->postJson('/api/avisos', [
        'titulo' => 'Firmado por otro',
        'contenido' => 'a ver si cuela',
        'categoria_id' => $this->categoria->id,
        'user_id' => $otro->id,
    ])->assertStatus(201);

    $this->assertDatabaseHas('posts', ['titulo' => 'Firmado por otro', 'user_id' => $this->editor->id]);
    $this->assertDatabaseMissing('posts', ['titulo' => 'Firmado por otro', 'user_id' => $otro->id]);
});
```

Pasa, y la razón es fina: `validate()` devuelve **solo las claves que validaste**, así que el `user_id` que venía en la petición se cae ahí, y el del token se pone después. La prueba convierte ese detalle en una garantía: si alguien cambia el `validate()` por un `$request->all()`, se pone roja.

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| `no such table: posts` | Falta `uses(RefreshDatabase::class);` |
| `Call to undefined method ...::factory()` | Falta `use HasFactory;` en el modelo |
| El 401 te llega como 302 | Usaste `$this->post()` en vez de `$this->postJson()` |
| `Class "Laravel\Sanctum\Sanctum" not found` | Falta el `use Laravel\Sanctum\Sanctum;` arriba del archivo |
| `assertJsonCount` falla diciendo que hay 0 | Tu `index()` filtra por `publicados()` y tus avisos de prueba nacieron con `publicado` en falso |
| El 201 pasa pero `assertDatabaseHas` falla en `user_id` | A tu `Post` le falta `'user_id'` en el `$fillable` |
| `Undefined property: $this->categoria` | El `beforeEach` está fuera del archivo o dentro de un `describe` que no corresponde |
| `Failed asserting that null is true.` en la prueba de la Policy | Armaste el usuario con `new User(['rol' => ...])` y `rol` no está en el `$fillable`. Usa `forceFill` |
| Las pruebas que crean avisos tardan segundos cada una | Falta `Queue::fake()` en el `beforeEach`: los correos se están mandando de verdad en cada prueba |
