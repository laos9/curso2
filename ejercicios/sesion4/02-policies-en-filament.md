# Ejercicio 2 · Tus Policies dentro del panel (~10 min en clase)

Filament no trae un sistema de permisos propio: **pregunta a la Policy de tu modelo** con los mismos nombres que Laravel usa por convención (`viewAny`, `create`, `update`, `delete`...). La `PostPolicy` que escribiste en la sesión 3 gobierna el panel sin que cambies nada. Lo que sí cambia es **qué pasa cuando un método no existe**, y ahí hay un hueco que conviene cerrar hoy.

## Paso 1 · Compruébalo con las dos cuentas (sin tocar la Policy)

| Como | Menú | Botones por fila |
|---|---|---|
| `editor@blog.test` | Avisos aparece | "Editar" y "Borrar" **solo** en los avisos con su `user_id` |
| `admin@blog.test` | Avisos aparece | los botones en todas las filas (es el `before()`) |

Filament decide fila por fila llamando a `update` y `delete` con ese registro. No escribiste ningún `@can` para el panel. Y si como editor abres a mano la URL de edición de un aviso ajeno (`/admin/posts/7/edit`), responde **403**: la misma decisión, en la misma clase, antes de tocar la base de datos.

## Paso 2 · Mira el hueco

Como editor, marca la casilla de varios avisos ajenos. Aparece **"Borrar seleccionados"** y funciona. Tu `delete()` protege fila por fila, pero el borrado masivo pregunta **`deleteAny`**, un método que tu Policy no tiene.

Y aquí está la regla que importa. Filament, cuando la Policy no tiene el método, **permite**:

```php
// filament/src/helpers.php
$policy = Gate::getPolicyFor($model);

if (filled($policy) && method_exists($policy, $action)) {
    return Gate::forUser($user)->inspect($action, [$model]);
}

// sin el método (y sin modo estricto): Response::allow()
```

Laravel, en tu blog público, hace lo contrario. `Gate::authorize` con un método que no existe **niega**, y ni siquiera llega al `before()`:

```php
// Illuminate\Auth\Access\Gate::resolvePolicyCallback
if (! is_callable([$policy, $this->formatAbilityToMethod($ability)])) {
    return false;
}
```

Misma ausencia, dos respuestas. Por eso Avisos aparece en el menú aunque no escribieras `viewAny`, y por eso el borrado masivo quedó abierto.

## Paso 3 · Cierra el borrado masivo

En `app/Policies/PostPolicy.php`:

```php
public function deleteAny(User $user): bool
{
    return false;   // nadie borra en masa; el admin pasa por before()
}
```

Nada que registrar. Comprueba: como editor, la casilla de selección desaparece; como admin, sigue ahí.

## Las llaves completas que Filament puede pedir

| Método | Cuándo lo pregunta | Si falta |
|---|---|---|
| `viewAny` | para mostrar el Resource en el menú y abrir la lista | permitido |
| `view` | para abrir un registro (si el Resource tiene página de detalle) | permitido |
| `create` | para el botón "Nuevo" y la página de alta | permitido |
| `update` | para el botón "Editar" de cada fila y la URL de edición | permitido |
| `delete` | para el botón "Borrar" de cada fila | permitido |
| `deleteAny` | para el borrado masivo desde la tabla | permitido |
| `restore`, `forceDelete`, `restoreAny`, `forceDeleteAny` | si el modelo usa soft deletes | permitido |
| `reorder` | si la tabla permite reordenar | permitido |

En los sistemas reales cada Policy trae los once métodos escritos, uno por uno, aunque la respuesta sea la misma: nadie depende del "permitido por ausencia". Filament trae además un modo estricto que, en vez de permitir, lanza un error cuando falta el método; viene apagado. Y existe `protected static bool $shouldSkipAuthorization = true;` en el Resource para apagar todas las preguntas: en producción no se usa.

## Para la tarea (nivel 2 de la semana)

Filament, en local, deja entrar al panel a **cualquier** usuario de la tabla. En producción exige que el modelo `User` diga quién puede:

```php
// app/Models/User.php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->rol, ['admin', 'editor']);
    }
}
```

Con eso, un usuario sin rol (o con otro rol) ve un 403 en `/admin` aunque su contraseña sea correcta. Es una capa distinta de las Policies: esta decide si entras al panel; aquellas, qué puedes tocar adentro.
