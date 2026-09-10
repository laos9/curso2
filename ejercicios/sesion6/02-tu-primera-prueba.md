# Ejercicio 2 · Tu primera prueba

Objetivo: dejar Pest instalado y escribir una prueba que corra en verde. Al terminar vas a tener `tests/Feature/Api/AvisosApiTest.php` con la primera prueba pasando.

Tiempo estimado: 25 minutos.

---

## Parte 0 · Ponte al día (solo si te falta la API)

Las pruebas de hoy se escriben sobre la API de la sesión 5. Comprueba que la tengas:

```bash
php artisan route:list --path=api
```

Si ves ocho rutas (`api/avisos`, `api/token`, `api/yo` y las demás), sigue al paso 1.

Si no ves nada o te faltan, el curso te la deja puesta:

```bash
bash .devcontainer/nivelar-api.sh
```

Ese script no toca ningún archivo que ya exista. Si te avisa que falta `HasApiTokens` en `app/Models/User.php`, agrégalo, son dos líneas:

```php
use Laravel\Sanctum\HasApiTokens;   // arriba, junto a los otros use

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
```

---

## Paso 1 · Instalar Pest

Pest es la forma en la que se escriben pruebas hoy en Laravel. Por dentro sigue siendo PHPUnit.

```bash
bash .devcontainer/preparar-pest.sh
```

O los dos comandos que ese script corre, si prefieres verlos:

```bash
composer require "pestphp/pest:^3.8" "pestphp/pest-plugin-laravel:^3.2" --dev --with-all-dependencies
./vendor/bin/pest --init
```

Te deja dos cosas: el paquete en `vendor/` y un archivo nuevo, `tests/Pest.php`.

**`php artisan pest:install` no existe.** Si lo escribes, artisan te va a decir que no conoce el comando. El que inicializa es `./vendor/bin/pest --init`.

---

## Paso 2 · Corre lo que ya tienes

Tu proyecto trae dos pruebas de fábrica desde la sesión 1, aunque nunca las hayas mirado:

```bash
php artisan test
```

```
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\ExampleTest

  Tests:    2 passed
```

Eso confirma que el motor corre. Ahora las tuyas.

---

## Paso 3 · Crea tu archivo

```bash
php artisan make:test Api/AvisosApiTest --pest
```

Se crea `tests/Feature/Api/AvisosApiTest.php` con un ejemplo dentro. Bórralo todo y deja el archivo así:

```php
<?php

use App\Models\Categoria;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('el listado responde 200', function () {
    $this->getJson('/api/avisos')->assertStatus(200);
});
```

Dos líneas merecen atención:

- **`uses(RefreshDatabase::class)`** le dice a Laravel que esta prueba necesita base de datos. Sin ella, la base de pruebas nace vacía, sin tablas.
- **`$this->getJson(...)`** manda la petición. La versión `Json` incluye el encabezado `Accept: application/json`, el mismo de la sesión 5.

Córrela:

```bash
php artisan test --filter=Avisos
```

Debe salir en verde. Si no, salta a la tabla del final.

---

## Paso 4 · Provoca un rojo

Una prueba que nunca has visto fallar no te consta que esté probando algo. Cambia el 200 por un 201:

```php
$this->getJson('/api/avisos')->assertStatus(201);
```

```
   FAILED  Tests\Feature\Api\AvisosApiTest > el listado responde 200
  Expected response status code [201] but received 200.
  Failed asserting that 200 is identical to 201.
```

Te está diciendo qué esperabas, qué llegó y en qué línea. Regrésalo a 200 y sigue.

---

## Paso 5 · Datos para la prueba: las factories

Tu prueba de arriba responde 200 con la lista vacía, porque la base de pruebas está limpia. Para probar de verdad hace falta que haya avisos, y eso lo fabrican las factories.

Crea las dos:

```bash
php artisan make:factory CategoriaFactory
php artisan make:factory PostFactory
```

`database/factories/CategoriaFactory.php`:

```php
public function definition(): array
{
    return [
        'nombre' => fake()->unique()->words(2, true),
    ];
}
```

`database/factories/PostFactory.php`:

```php
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
```

Acuérdate de los `use App\Models\Categoria;` y `use App\Models\User;` arriba del archivo.

Ahora amplía la prueba para que compruebe algo:

```php
test('el listado trae solo los avisos publicados', function () {
    Post::factory()->count(3)->create(['publicado' => true]);
    Post::factory()->create(['publicado' => false]);

    $this->getJson('/api/avisos')
        ->assertStatus(200)
        ->assertJsonCount(3, 'data');
});
```

Cuatro avisos creados, tres publicados, y la API debe devolver tres. Esa prueba está comprobando **tu** `scopePublicados` de la sesión 2, no el framework.

---

## Paso 6 · El error que casi todos ven aquí

Al correr lo anterior es probable que salga esto:

```
BadMethodCallException: Call to undefined method App\Models\Categoria::factory()
```

Los modelos que escribiste en la sesión 2 no traen el trait que habilita las factories. Agrégalo en `app/Models/Post.php` y en `app/Models/Categoria.php`:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;
    // ...
```

Vuelve a correr. Verde.

---

## Checkpoint

Antes de pasar al ejercicio 3, comprueba:

1. `php artisan test` corre sin errores de configuración.
2. Tienes `tests/Feature/Api/AvisosApiTest.php` con al menos una prueba en verde.
3. `Post::factory()->create()` funciona (o sea, ya pusiste `HasFactory`).
4. Viste un rojo a propósito y entendiste qué te decía.

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| `no such table: posts` | Falta `uses(RefreshDatabase::class);` en tu archivo de prueba |
| `Call to undefined method ...::factory()` | Falta `use HasFactory;` en el modelo |
| `Command "pest:install" is not defined` | Ese comando no existe. Usa `./vendor/bin/pest --init` |
| `Class "Tests\TestCase" not found` | Falta `tests/Pest.php`. Corre `./vendor/bin/pest --init` |
| Todo responde `302` en vez de `200` o `401` | Usaste `$this->get()` en vez de `$this->getJson()` |
| `Target class [App\Http\Controllers\Api\PostController] does not exist` | Te falta la API de la sesión 5. Corre `bash .devcontainer/nivelar-api.sh` |
| La primera prueba tarda más que las demás | Es normal: `RefreshDatabase` monta el esquema en memoria antes de empezar. Es cuestión de décimas |
