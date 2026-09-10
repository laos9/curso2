# Ejercicio 1 · Colas: que tu API no espere

Objetivo: que crear un aviso por tu API deje el correo en una cola en vez de mandarlo ahí mismo, y ver la diferencia en el probador.

Tiempo estimado: 10 minutos.

La explicación completa de colas, con los fallos y los reintentos, está en la lectura de la semana pasada: `ejercicios/sesion5/03-colas.md`. Aquí va el caso concreto, en tu API.

---

## El caso

Cuando alguien crea un aviso, hay que avisar por correo a los usuarios. Mandar un correo tarda: un servidor de correo real contesta en uno o dos segundos por mensaje, y con muchos usuarios son minutos.

Si eso pasa dentro de `store()`, quien llamó a tu API se queda esperando todo ese tiempo antes de ver su 201. Con una cola, la respuesta sale de inmediato y el correo lo manda otro proceso después.

Para no depender de un servidor de correo, el tiempo se simula con un `sleep(3)`.

---

## Paso 1 · El worker ya está corriendo

Mira la terminal donde corre `composer run dev`. Uno de sus cuatro procesos es `php artisan queue:listen`, con algunas opciones después.

Ese es el **worker**: un proceso que espera trabajos y los ejecuta cuando llegan. Lleva ahí desde la sesión 1. Y la lista de pendientes es la tabla `jobs` de tu base de datos, porque tu `.env` dice `QUEUE_CONNECTION=database`. No hay nada que instalar.

---

## Paso 2 · Crea el trabajo

```bash
php artisan make:job EnviarAvisoPorCorreo
```

Deja `app/Jobs/EnviarAvisoPorCorreo.php` así:

```php
<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnviarAvisoPorCorreo implements ShouldQueue
{
    use Queueable;

    public function __construct(public Post $post) {}

    public function handle(): void
    {
        sleep(3);   // aqui iria el envio real a cada usuario
        Log::info('Aviso enviado por correo: '.$this->post->titulo);
    }
}
```

- **`implements ShouldQueue`** es lo que manda el trabajo a la cola. Sin esa interfaz se ejecuta ahí mismo, dentro de la petición.
- **`handle()`** es lo que hace el worker cuando le toca. El constructor solo guarda el aviso.

---

## Paso 3 · Despáchalo al crear un aviso

En `app/Http/Controllers/Api/PostController.php`, arriba con los demás `use`:

```php
use App\Jobs\EnviarAvisoPorCorreo;
```

Y en `store()`, justo después de crear el aviso:

```php
$post = Post::create($datos);

EnviarAvisoPorCorreo::dispatch($post);
```

Es una línea. `dispatch()` no ejecuta el trabajo: lo deja en la tabla `jobs` y sigue.

---

## Paso 4 · Míralo en el probador

1. Abre el probador, pide tu token y crea un aviso con `POST /api/avisos`.
2. Fíjate en el tiempo que marca la respuesta: **menos de un segundo**, aunque el trabajo tarde tres.
3. Mira la terminal de `composer run dev`: aparece `App\Jobs\EnviarAvisoPorCorreo` en `RUNNING`, y unos segundos después en `DONE`.
4. En `storage/logs/laravel.log` quedó la línea `Aviso enviado por correo:` con el título de tu aviso.

---

## Paso 5 · Quítale la cola y compara

Borra `implements ShouldQueue` de la clase y crea otro aviso en el probador.

Ahora la respuesta tarda **más de tres segundos**: el correo se mandó dentro de la petición, y quien llamó a tu API lo esperó entero. Esa es la diferencia que hace la cola.

Regresa `implements ShouldQueue` antes de seguir.

---

## Checkpoint

1. Con la cola, el `POST /api/avisos` responde en menos de un segundo y el worker muestra el trabajo en `DONE` después.
2. Sin la cola, el mismo `POST` tarda más de tres segundos.
3. `implements ShouldQueue` está de vuelta en su lugar.

---

## Qué mandar a una cola

La pregunta que decide: **¿quien llamó necesita el resultado para continuar?** Si no lo necesita (un correo, un PDF, un reporte, avisarle a otro sistema), va a la cola. Si la respuesta lo necesita para armarse, va dentro.

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| El `POST` tarda más de tres segundos aunque tengas la cola | Falta `implements ShouldQueue`, o tu `.env` dice `QUEUE_CONNECTION=sync` |
| El trabajo nunca aparece en la terminal | No está corriendo `composer run dev`. Levántalo, o corre `php artisan queue:work --stop-when-empty` |
| `Class "App\Jobs\EnviarAvisoPorCorreo" not found` | Falta el `use App\Jobs\EnviarAvisoPorCorreo;` en el controlador |
| El trabajo falla y deja de aparecer | Se mudó a la tabla `failed_jobs`: `php artisan queue:failed` para verlo y `php artisan queue:retry all` para reintentarlo |
| Cambiaste el `handle()` y el worker sigue haciendo lo de antes | `queue:listen` recarga solo. Si usas `queue:work`, reinícialo con `php artisan queue:restart` |
