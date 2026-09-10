# Ejercicio 2 · Quién puede escribir en tu API (~25 min en clase)

Tu blog sabe quién eres por una **cookie de sesión**: entras una vez, el navegador guarda la cookie y la manda sola en cada clic. Un programa que consume tu API no tiene navegador, no guarda cookies y no llena formularios. Necesita otra forma de identificarse, y esa forma es un **token**.

## Paso 1 · El trait que pidió el comando (5 min)

Cuando corriste `php artisan install:api` terminó pidiendo esto:

```
Please add the [Laravel\Sanctum\HasApiTokens] trait to your User model.
```

En `app/Models/User.php`:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;
```

Ese trait le da al modelo `createToken()`, `tokens()` y `currentAccessToken()`. Sin él, el resto de la sesión no compila.

## Paso 2 · La ruta que entrega tokens (8 min)

```bash
php artisan make:controller Api/TokenController
```

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function crear(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'dispositivo' => ['required'],
        ]);

        $usuario = User::where('email', $datos['email'])->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => 'Esas credenciales no coinciden con nuestros registros.',
            ]);
        }

        return [
            'token' => $usuario->createToken($datos['dispositivo'])->plainTextToken,
            'usuario' => $usuario->name,
            'rol' => $usuario->rol,
        ];
    }

    public function revocar(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ['mensaje' => 'Token revocado'];
    }
}
```

Compáralo con tu `AuthController` de la sesión 3. La primera mitad es idéntica: validar, buscar el usuario, comparar la contraseña con `Hash::check`. Lo que cambia es el final. Ahí abrías una sesión con `Auth::attempt` y redirigías; aquí devuelves una cadena.

Tres detalles que importan:

- **`dispositivo`** es el nombre con el que se guarda ese token. Sirve para revocar el del teléfono que perdiste sin tumbar los demás. En la tabla `personal_access_tokens` vas a ver esa columna con el valor que mandes.
- **`plainTextToken` es la única vez que el token se ve completo.** En la tabla queda guardado su hash, igual que una contraseña. Si el usuario lo pierde, no se recupera: se genera otro.
- El error de credenciales sale como **422**, con el mismo formato que cualquier otra validación. Quien consume tu API ya sabe leer esa forma.

## Paso 3 · El candado (7 min)

En `routes/api.php`:

```php
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;

Route::post('/token', [TokenController::class, 'crear']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/yo', fn (Request $request) => [
        'id' => $request->user()->id,
        'nombre' => $request->user()->name,
        'rol' => $request->user()->rol,
    ]);
    Route::post('/avisos', [PostController::class, 'store']);
    Route::put('/avisos/{post}', [PostController::class, 'update']);
    Route::delete('/avisos/{post}', [PostController::class, 'destroy']);
    Route::post('/token/revocar', [TokenController::class, 'revocar']);
});
```

Es el mismo `Route::middleware(...)->group(...)` de la sesión 3, con otro guardia: allí `auth` revisaba la sesión, aquí `auth:sanctum` revisa el encabezado `Authorization: Bearer <token>`.

`GET /api/yo` no sirve para nada en producción y sirve mucho ahora: es la forma más rápida de saber si tu token funciona.

Fíjate en que no devuelve `$request->user()` a secas. Devolverlo publicaría la tabla `users` completa (correo, fecha de verificación, fechas internas), que es exactamente el problema que resolviste con el `PostResource`. La misma regla aplica en las respuestas de dos líneas.

✅ **Checkpoint A:** en el probador, pide un token con `POST /api/token` (viene lleno con `editor@blog.test` y `secreto123`). Al recibirlo, la etiqueta de arriba cambia a **token activo**. Luego manda `GET /api/yo` y confirma que responde **200** con tus datos.

## Paso 4 · Cuando pides JSON y te llega HTML (5 min)

Prueba `POST /api/avisos` **sin token** desde el probador. Responde **401** y el cuerpo dice `Unauthenticated.`

Ahora prueba lo mismo desde la terminal, sin encabezados:

```bash
curl -i -X POST https://<tu-codespace>-8000.app.github.dev/api/avisos -d 'titulo=x'
```

No responde 401. Responde **302** y te manda al login de tu blog.

La razón: cuando el middleware `auth` no encuentra usuario, tiene que decidir entre responder un error o mandarte a iniciar sesión, y decide **según lo que tú pediste**. Si la petición dice `Accept: application/json`, responde 401 en JSON. Si no dice nada, asume que hay un navegador del otro lado y redirige.

```bash
curl -i -X POST -H 'Accept: application/json' \
  https://<tu-codespace>-8000.app.github.dev/api/avisos -d 'titulo=x'
```

Eso sí responde 401. **Regla práctica: si te llega HTML donde esperabas JSON, te faltó ese encabezado.** El probador lo manda siempre, por eso ahí nunca lo ves.

## Paso 5 · Tu Policy ya estaba aquí (5 min)

Tu `store`, `update` y `destroy` llaman `Gate::authorize`, igual que en la sesión 3. No cambiaste nada. Compruébalo:

1. Con el token del editor, edita un aviso **tuyo** con `PUT /api/avisos/{id}`. Responde **200**.
2. Con el mismo token, edita un aviso de **otra persona**. Responde **403**, y el cuerpo dice `This action is unauthorized.`

Esa decisión no la tomó la API. La tomó `PostPolicy::update()`, el archivo que escribiste hace dos semanas, que hoy gobierna **tres caras** del mismo blog: el botón Editar de tu portada, el botón Editar del panel de Filament y este endpoint. Una regla, escrita una vez, aplicada en tres lugares que no se conocen entre sí.

> Si `APP_DEBUG=true`, el cuerpo del 403 trae además la traza completa del error. El mensaje real es la primera línea. En producción esa traza no viaja.

## Paso 6 · Tres agujeros de seguridad de una API (5 min)

Una API es la puerta más expuesta de un sistema: no tiene pantalla que disimule y quien la llama no es un navegador que respete tus reglas.

**1. El token filtrado.** Un token es una contraseña. Si se te va en un commit, en una captura de pantalla o en un mensaje, quien lo tenga es tú. Por eso existen dos cosas: `dispositivo` para saber cuál revocar, y `POST /api/token/revocar` para matarlo. Pruébalo: revoca tu token y manda `GET /api/yo` otra vez. Responde **401**.

**2. La asignación masiva.** Tu `store` hace `Post::create($datos)` con lo que llegó del cliente. Lo único que impide que alguien mande `"user_id": 1` y cree un aviso a nombre de otro es el `$fillable` de tu modelo y que tú sobreescribes `$datos['user_id']` después de validar. Quita esa línea y pruébalo desde el probador para verlo. Luego vuelve a ponerla.

**3. Sin límite de peticiones.** Nada impide que alguien pida un token diez mil veces por minuto. Laravel trae el freno:

```php
// routes/api.php
Route::post('/token', [TokenController::class, 'crear'])
    ->middleware('throttle:6,1');
```

Seis intentos por minuto por dirección. Si se pasan, responde **429**. Póntelo al endpoint de token, que es el que interesa atacar.

## Para la tarea

Falta que la API distinga a un editor de un administrador, igual que tu Policy. Cuando el admin edite un aviso ajeno por la API, debe responder 200 gracias al `before()`. Compruébalo con el token del admin.

## Problemas comunes

| Síntoma | Causa probable |
|---|---|
| `Call to undefined method createToken()` | Falta el trait `HasApiTokens` en `User` |
| Recibes HTML del login en vez de 401 | Falta `Accept: application/json` |
| Siempre 401 aunque el token sea nuevo | El encabezado va como `Authorization: Bearer <token>`, con el espacio y con `Bearer` |
| 403 hasta en tus propios avisos | El aviso no tiene `user_id`, o lo creaste antes de la sesión 3 |
| 419 en vez de 401 | Estás mandando la petición a una ruta de `web.php`, no de `api.php`: ahí sí hay CSRF |
