# Las pruebas por dentro · la versión escrita de la sesión 6

Esta es la explicación completa del bloque conceptual de la clase, para leerla con calma. Si estuviste en la sesión, aquí está lo mismo con más detalle y con el código a la mano.

---

## 1. La semana pasada probaste tu API a mano. Hoy lo escribes

En la sesión 5 abriste el probador y fuiste haciendo, uno por uno:

1. `GET /api/avisos` y viste un 200 con tus avisos.
2. `POST /api/avisos` sin token y viste un 401.
3. Pediste un token y volviste a intentar: 201.
4. Intentaste editar un aviso ajeno: 403.

Eso es una prueba. Lo que cambia hoy es que en vez de hacerlo con el dedo, lo escribes una vez y lo corre la computadora. La diferencia práctica: mañana cambias una línea del controlador y en unos segundos sabes si rompiste alguno de esos cuatro casos, sin volver a abrir el probador.

```php
test('sin token responde 401', function () {
    $this->postJson('/api/avisos', ['titulo' => 'x'])->assertStatus(401);
});
```

Ese archivo es el punto 2 de la lista de arriba, escrito. Nada más.

---

## 2. La aserción, la única idea nueva

Una prueba es código normal con una frase distinta: la **aserción**. Una aserción declara qué esperas, y si no se cumple, la prueba falla.

```php
$respuesta->assertStatus(201);                                    // espero que el codigo sea 201
$this->assertDatabaseHas('posts', ['titulo' => 'Capacitacion']);  // espero que esa fila exista
expect($post->fresh()->titulo)->not->toBe('Secuestrado');         // espero que NO haya cambiado
```

Todo lo demás (crear un usuario, mandar la petición, leer la respuesta) es PHP del que ya escribes. Si sabes usar Eloquent y sabes qué responde tu API, ya sabes casi todo lo que hace falta.

---

## 3. Rojo y verde

Una prueba tiene dos estados y conviene ver los dos.

**Verde** es cuando pasa:

```
   PASS  Tests\Feature\Api\AvisosApiTest
  ok  escribir avisos, sin token responde 401                            0.36s
```

**Rojo** es cuando falla, y te dice exactamente qué esperabas y qué llegó:

```
   FAILED  Tests\Feature\Api\RojoTest > el listado responde 200
  Expected response status code [201] but received 200.
  Failed asserting that 200 is identical to 201.

  at tests/Feature/Api/RojoTest.php:6
      5: test('el listado responde 200', function () {
   >  6:     $this->getJson('/api/avisos')->assertStatus(201);
      7: });
```

La flecha apunta a la línea exacta. Un rojo no es un problema, es información: te está diciendo dónde y qué.

Vale la pena provocar un rojo a propósito la primera vez. Una prueba que nunca has visto fallar no te consta que esté probando algo.

---

## 4. Dónde viven las pruebas

Tu proyecto ya trae la carpeta desde el primer día:

```
tests/
  Feature/          <- pruebas que atraviesan tu aplicacion completa
    ExampleTest.php
  Unit/             <- pruebas de una pieza aislada
    ExampleTest.php
  TestCase.php
```

Casi todas las de hoy son **de integración** (Laravel les dice *Feature*): mandan una petición HTTP de verdad a tu aplicación, pasan por las rutas, por el middleware, por el controlador, por la Policy y por la base de datos. Es el mismo recorrido del laboratorio "el viaje completo de una petición" de la sesión 5, pero disparado desde un archivo.

Se organizan en carpetas como el resto del proyecto. Vas a crear `tests/Feature/Api/` porque estás probando la API.

---

## 5. La pirámide, y por qué hoy casi todo es de integración

Se habla de tres niveles de prueba:

| Nivel | Qué prueba | Ejemplo en tu blog |
|---|---|---|
| **Unitaria** (`tests/Unit`) | Una pieza sola, sin base de datos ni HTTP | Que tu `PostPolicy` le diga que no a un editor sobre un aviso ajeno |
| **Integración** (`tests/Feature`) | Un caso completo, de la petición a la respuesta | Que `POST /api/avisos` sin token responda 401 |
| **End to end** | La aplicación real con un navegador de verdad | Que al llenar el formulario y dar clic aparezca el aviso |

La pirámide clásica dice muchas unitarias, algunas de integración, poquísimas de punta a punta. En Laravel la realidad es otra: **la mayor parte del valor está en integración**, porque casi todo lo interesante de una aplicación web sucede en el cruce de rutas, autorización y base de datos, y eso una prueba unitaria no lo ve. En un sistema real en producción, con más de cien modelos, la carpeta `Feature` es la que crece.

Regla práctica: si la respuesta a "¿esto se rompió?" se contesta abriendo el navegador o el probador, entonces es una prueba de integración.

Sobre los nombres: Laravel le dice *Feature* a lo que en el resto de la industria se llama prueba de integración. Es el mismo nivel con otro nombre, y vas a oír las dos palabras.

### La misma regla en los dos niveles

Tu `PostPolicy` se puede probar de las dos formas. La unitaria la llama directo, con modelos armados en memoria que nunca se guardan:

```php
test('un editor no puede editar el aviso de otro', function () {
    $yo = (new User)->forceFill(['id' => 1, 'rol' => 'editor']);
    $ajeno = (new Post)->forceFill(['user_id' => 2]);

    expect((new PostPolicy)->update($yo, $ajeno))->toBeFalse();
});
```

La de integración manda el `PUT` y espera el 403. Si quitas el `Gate::authorize` del controlador, la unitaria sigue en verde (la regla está bien escrita) y la de integración se pone roja (nadie la está llamando). **La unitaria te dice que la regla está bien; la de integración, que alguien la usa.**

Un detalle al armar el usuario: `new User(['rol' => 'admin'])` tira el `rol` en silencio porque no está en el `$fillable`, y la prueba falla con `Failed asserting that null is true.` Por eso `forceFill`, que se salta esa protección.

---

## 6. Pest y PHPUnit: cuál es cuál

Los vas a ver nombrados juntos y confunde. Es simple:

- **PHPUnit** es el motor. Existe desde hace veinte años, corre las pruebas y produce el resultado.
- **Pest** es la forma de escribirlas. Por dentro llama a PHPUnit.

La misma prueba, en los dos estilos:

```php
// PHPUnit: una clase, un metodo por prueba
class AvisosTest extends TestCase
{
    public function test_sin_token_responde_401(): void
    {
        $this->postJson('/api/avisos', ['titulo' => 'x'])->assertStatus(401);
    }
}
```

```php
// Pest: una funcion con el nombre en español
test('sin token responde 401', function () {
    $this->postJson('/api/avisos', ['titulo' => 'x'])->assertStatus(401);
});
```

Fíjate en lo que **no** cambia: `$this->postJson(...)` y `assertStatus(...)` son idénticos en los dos. Eso es porque los dos corren sobre PHPUnit. Lo que Pest te ahorra es la clase, el `public function`, los guiones bajos en los nombres y el `: void`.

Usamos Pest porque es lo que se escribe hoy en Laravel y lo que vas a encontrar en un proyecto real: **Pest 3 sobre PHPUnit 11**.

---

## 7. La base de datos de las pruebas nace y muere en cada corrida

La duda razonable: si la prueba crea avisos, ¿me va a llenar de basura mi base de datos?

No, y no tienes que configurar nada. Tu proyecto ya trae esto en `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Cuando corres pruebas, Laravel cambia la conexión a **una base de datos sqlite que vive en la memoria RAM**. No es un archivo, no es tu base de desarrollo, no existe cuando termina la corrida. Tus datos ni se enteran.

Falta la segunda mitad: esa base nace **vacía**, sin tablas. Quien las crea es este trait, al principio del archivo de prueba:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
```

`RefreshDatabase` corre tus migraciones antes de las pruebas y envuelve cada prueba en una transacción que deshace al terminar. La consecuencia importante: **cada prueba empieza con la base vacía**. Si la prueba A crea tres avisos, la prueba B no los ve. Eso es a propósito, y es lo que hace que el orden de las pruebas no importe.

Si se te olvida esa línea, el error es inconfundible:

```
SQLSTATE[HY000]: General error: 1 no such table: posts
```

No es que tu base esté rota. Es que la de la prueba está vacía porque nadie corrió las migraciones.

Tu `phpunit.xml` cambia otra cosa: `QUEUE_CONNECTION` pasa a `sync`, o sea que en las pruebas los trabajos en cola se ejecutan ahí mismo. Si tu `store()` despacha un correo que tarda tres segundos, cada prueba que crea un aviso esperaría esos tres segundos. Para eso está `Queue::fake()`, al principio de la prueba: guarda los trabajos sin ejecutarlos y te deja comprobar después que se despacharon.

---

## 8. Factories: los datos de mentira

Si cada prueba arranca con la base vacía, cada prueba tiene que crear lo que necesita. Escribir eso a mano sería insufrible:

```php
// asi NO
$categoria = Categoria::create(['nombre' => 'Avisos']);
$user = User::create(['name' => 'Ana', 'email' => 'ana@ejemplo.com', 'password' => bcrypt('x')]);
$post = Post::create(['titulo' => '...', 'contenido' => '...', 'categoria_id' => $categoria->id, 'user_id' => $user->id, 'publicado' => true]);
```

Para eso existen las **factories**: una receta de cómo se ve un registro cualquiera de ese modelo.

```php
// database/factories/PostFactory.php
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(4),
            'contenido' => fake()->paragraph(),
            'categoria_id' => Categoria::factory(),
            'user_id' => User::factory(),
            'publicado' => true,
        ];
    }
}
```

Con eso, en la prueba:

```php
Post::factory()->create();                        // un aviso, con todo lo que necesita
Post::factory()->count(20)->create();             // veinte
Post::factory()->create(['publicado' => false]);  // uno, pero borrador
```

Tres cosas que vale la pena entender:

1. **`fake()`** inventa los datos. No te importa qué diga el título, te importa que haya un aviso.
2. **`Categoria::factory()` dentro de la definición** significa "y si no me das una categoría, fabrica una". Por eso `Post::factory()->create()` funciona sin haber creado nada antes.
3. **Lo que pasas a `create()` gana** sobre la receta. Así fijas solo el dato que le importa a esa prueba y dejas que el resto se invente.

Un detalle de estados, que ahorra repetir:

```php
public function borrador(): static
{
    return $this->state(fn () => ['publicado' => false]);
}

// y en la prueba:
Post::factory()->borrador()->create();
```

**Ojo con esto:** los modelos que escribiste en la sesión 2 no traen el trait que habilita las factories. Si te falta, el error es:

```
BadMethodCallException: Call to undefined method App\Models\Categoria::factory()
```

La solución son dos líneas en el modelo:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;
```

---

## 9. Cómo se manda una petición desde una prueba

Laravel trae un cliente HTTP para pruebas. No levanta un servidor ni abre un navegador: mete la petición directo en tu aplicación.

```php
$this->getJson('/api/avisos');
$this->postJson('/api/avisos', ['titulo' => 'x', 'contenido' => 'y', 'categoria_id' => 1]);
$this->putJson("/api/avisos/{$post->id}", [...]);
$this->deleteJson("/api/avisos/{$post->id}");
```

Las versiones `...Json` mandan el encabezado `Accept: application/json` por ti. Es justo el encabezado de la sesión 5, el que separa un 401 de un 302 al login. Si usas `$this->post()` en vez de `$this->postJson()`, vas a recibir la redirección y la prueba va a fallar por una razón que no tiene que ver con lo que querías probar.

Para identificarte:

```php
use Laravel\Sanctum\Sanctum;

Sanctum::actingAs($usuario);   // a partir de aqui, las peticiones van con token
```

`Sanctum::actingAs` no pide un token de verdad ni llama a `/api/token`: le dice a la aplicación "trata esta petición como si viniera con el token de este usuario". Eso te deja probar las rutas protegidas sin repetir el login en cada prueba.

---

## 10. Las aserciones que vas a usar hoy

| Aserción | Qué comprueba |
|---|---|
| `assertStatus(201)` | El código de estado de la respuesta |
| `assertJsonCount(3, 'data')` | Que el arreglo `data` traiga tres elementos |
| `assertJsonPath('data.0.titulo', 'Cambio de horario')` | Un valor concreto dentro del JSON |
| `assertJsonStructure(['token', 'usuario', 'rol'])` | Que existan esas llaves, sin importar su valor |
| `assertJsonValidationErrors(['titulo'])` | Que el 422 se queje de ese campo |
| `assertDatabaseHas('posts', [...])` | Que la fila exista en la base |
| `assertDatabaseMissing('posts', [...])` | Que NO exista |
| `assertDatabaseCount('personal_access_tokens', 0)` | Cuántas filas hay |
| `Queue::assertPushed(EnviarAvisoPorCorreo::class)` | Que el trabajo quedó en la cola. Pide `Queue::fake()` antes |
| `expect($valor)->toBe(...)` | La aserción general de Pest, para lo que no sea HTTP ni base de datos |

Nota sobre `data`: tu API responde con las llaves `data`, `links` y `meta` porque el controlador usa `paginate()` con `PostResource::collection()`. Por eso las aserciones dicen `'data'` y no la raíz.

---

## 11. Qué no se prueba

La tentación del primer día es probar todo. No hace falta:

- **No pruebes el framework.** Que `Post::create()` guarde en la base es responsabilidad de Laravel, y Laravel ya tiene sus propias pruebas.
- **No pruebes tus getters.** Que `$post->titulo` devuelva el título no aporta nada.
- **Sí prueba tus decisiones.** Quién puede escribir, qué campos salen, qué código de estado responde cada caso, qué pasa cuando los datos vienen mal. Todo eso lo decidiste tú, y eso es lo que se puede romper.

La pregunta útil: **¿esto lo decidí yo?** Si la respuesta es sí, merece una prueba.

---

## 12. Cuánto tarda

Poco. En un proyecto como el tuyo, la suite completa de esta sesión (diecinueve pruebas) corre en menos de un segundo en una máquina rápida, y en un par de segundos en una modesta. La primera prueba que usa la base tarda un poco más que las demás, porque `RefreshDatabase` monta el esquema en memoria; las siguientes van en centésimas.

Por eso se corren tan seguido: si la suite tarda segundos, no hay razón para no correrla cada vez que cambias algo. Lo que sí la vuelve lenta es que una prueba haga trabajo real que no le toca, como mandar el correo de la cola. Para eso está `Queue::fake()` (sección 7).

---

## 13. Los comandos

```bash
# instalar Pest (una sola vez)
composer require "pestphp/pest:^3.8" "pestphp/pest-plugin-laravel:^3.2" --dev --with-all-dependencies
./vendor/bin/pest --init

# correr todo
php artisan test

# correr un archivo o un grupo
php artisan test --filter=Avisos

# crear un archivo de prueba
php artisan make:test Api/AvisosApiTest --pest
```

Dos avisos sobre los comandos:

- **`php artisan pest:install` no existe.** El que inicializa es `./vendor/bin/pest --init`.
- `php artisan test` y `./vendor/bin/pest` corren lo mismo. El primero es el que ya conoces y el que lee tu `phpunit.xml`.

---

## 14. Cómo se ve esto en un proyecto de verdad

En un sistema en producción con más de cien modelos, la carpeta `tests/` tiene más de doscientos archivos, organizados por módulo, y una carpeta `Feature/Api/` con la misma forma de lo que vas a escribir hoy: un archivo por área, un `describe` por grupo de casos, un `test` por caso.

La suite se corre en cada cambio antes de integrarlo. Ese es el punto de todo esto: no es un ejercicio escolar, es lo que permite que varias personas toquen el mismo sistema sin romperse el trabajo entre ellas.

---

## Glosario

| Término | Qué es |
|---|---|
| **Prueba** (test) | Un archivo con código que ejerce tu aplicación y declara qué espera |
| **Aserción** | La frase que declara lo esperado. Si no se cumple, la prueba falla |
| **Verde y rojo** | Que la prueba pase, o que falle |
| **Prueba de integración** (Feature) | Prueba que atraviesa la aplicación completa, de la petición a la respuesta. Laravel las guarda en `tests/Feature` |
| **Prueba unitaria** (Unit) | Prueba de una pieza aislada, sin HTTP ni base de datos. Laravel las guarda en `tests/Unit` |
| **`Queue::fake()`** | Guardar los trabajos en cola sin ejecutarlos, para comprobar en la prueba que se despacharon |
| **`forceFill`** | Llenar un modelo saltándose el `$fillable`. En una prueba unitaria sirve para armar un usuario con `rol` |
| **PHPUnit** | El motor que corre las pruebas |
| **Pest** | La forma moderna de escribirlas. Por dentro es PHPUnit |
| **Factory** | Receta para fabricar registros de mentira para las pruebas |
| **`fake()`** | El generador de datos inventados que usan las factories |
| **`RefreshDatabase`** | Trait que crea el esquema y deja la base limpia entre pruebas |
| **`:memory:`** | Base de datos sqlite que vive en RAM y desaparece al terminar |
| **`Sanctum::actingAs`** | Decirle a la aplicación que la petición viene con el token de ese usuario |
| **Suite** | El conjunto de todas las pruebas del proyecto |
