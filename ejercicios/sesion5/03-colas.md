# Lectura · Colas y trabajos en segundo plano (~20 min, con comandos)

> Este material no cabía en clase, así que va aquí completo, con comandos que puedes correr en tu proyecto tal como está. Al final hay una pregunta que va en tu Pull Request.

## El problema, con números

Tu endpoint `POST /api/avisos` responde en unos 200 milisegundos. Ahora imagina que al crear un aviso hay que mandar un correo a 300 personas. Mandar un correo tarda entre medio segundo y dos segundos. Trescientos correos son varios minutos.

Si eso ocurre dentro de la petición, pasan tres cosas: quien llamó a tu API se queda esperando minutos, el navegador o el cliente se rinde antes con un error de tiempo agotado, y si algo truena a la mitad, no sabes cuáles correos salieron.

**Una cola separa "aceptar el trabajo" de "hacer el trabajo".** La petición responde de inmediato, y el trabajo pesado lo hace después otro proceso.

## Lo que ya tienes corriendo sin saberlo

Abre la terminal donde corre `composer run dev`. Son cuatro procesos, y uno de ellos es este:

```
php artisan queue:listen --tries=1
```

Ese es un **worker**: un proceso que se queda mirando una lista de pendientes y toma el siguiente cuando aparece. Lleva ahí desde la sesión 1.

Y la lista de pendientes también existe ya. En `.env`:

```
QUEUE_CONNECTION=database
```

O sea que los pendientes se guardan en una tabla de tu base de datos, `jobs`, que se creó con la primera migración del proyecto. No hay nada que instalar.

## Un trabajo, de punta a punta

```bash
php artisan make:job EnviarAvisoPorCorreo
```

Queda en `app/Jobs/EnviarAvisoPorCorreo.php`:

```php
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
        sleep(3);   // aqui iria el envio real
        Log::info('Aviso enviado por correo: ' . $this->post->titulo);
    }
}
```

Dos piezas importan:

- **`implements ShouldQueue`** es lo único que separa un trabajo en cola de una clase normal. Sin esa interfaz, el trabajo se ejecuta ahí mismo y no ganas nada.
- **`handle()`** es lo que se ejecuta cuando le toca. El constructor solo guarda datos.

## Míralo pasar

Detén `composer run dev` para que no haya worker, y despacha el trabajo:

```bash
php artisan tinker --execute='App\Jobs\EnviarAvisoPorCorreo::dispatch(App\Models\Post::first()); echo "despachado";'
```

Responde al instante, aunque el trabajo tarde tres segundos. Eso es lo que gana tu petición.

Ahora mira la lista de pendientes:

```bash
php artisan tinker --execute='echo DB::table("jobs")->count();'
```

Responde `1`. Ahí está, esperando. Levanta un worker:

```bash
php artisan queue:work --stop-when-empty
```

```
2026-09-06 21:35:10 App\Jobs\EnviarAvisoPorCorreo ....................... RUNNING
2026-09-06 21:35:13 App\Jobs\EnviarAvisoPorCorreo ........................ 3s DONE
```

Vuelve a contar la tabla: ahora es `0`. Y en `storage/logs/laravel.log`:

```
[2026-09-06 21:35:13] local.INFO: Aviso enviado por correo: Cambio de horario en barandilla
```

Tres segundos que tu petición no esperó.

## Cuando el trabajo falla

Los trabajos fallan: el servidor de correo no responde, la red se cae, un dato viene mal. Provócalo. Cambia el `handle()`:

```php
public function handle(): void
{
    throw new \RuntimeException('El servidor de correo no responde');
}
```

Despacha otra vez y corre el worker con reintentos:

```bash
php artisan queue:work --tries=3 --stop-when-empty
```

Verás tres intentos y luego `FAIL`. El trabajo no se pierde: se muda a la tabla `failed_jobs`.

```bash
php artisan queue:failed
```

Te lista los fallidos con su identificador y el error. Cuando arregles la causa:

```bash
php artisan queue:retry all
```

Vuelven a la cola y se intentan de nuevo. Esa es la ventaja que no se ve al principio: **un trabajo que falla queda registrado y se puede repetir**, mientras que una línea de código que truena dentro de una petición se pierde con la petición.

## Dos comandos que vas a necesitar

- **El worker no ve tus cambios de código.** Un worker carga el código una vez y se queda corriendo. Si editas el `handle()`, tienes que reiniciarlo: `php artisan queue:restart`. `queue:listen` (el de `composer run dev`) sí recarga en cada trabajo, y por eso es más lento y solo sirve para desarrollo.
- **En producción** el worker no se lanza a mano: lo mantiene vivo un supervisor del sistema, que lo vuelve a levantar si se cae.

## Qué mandar a una cola y qué no

**Sí:** correos y notificaciones, generar PDFs o reportes, procesar imágenes que subieron, llamar a otro sistema por API, importar un archivo grande, cualquier cosa que tarde más de un segundo y que quien llamó no necesita esperar.

**No:** lo que la respuesta necesita para armarse. Si el usuario tiene que ver el resultado en esa misma pantalla, no puede ir a una cola.

La regla: **¿quien llamó necesita el resultado para continuar?** Si sí, va dentro. Si no, va a la cola.

## Tu pregunta para el Pull Request

De los endpoints que escribiste en la sesión 5, elige uno que se beneficiaría de una cola. Escribe en tres líneas: **cuál es**, **qué parte mandarías a la cola**, y **qué le responderías a quien llamó mientras el trabajo sigue pendiente**.

No hay una sola respuesta correcta. Lo que se evalúa es que la parte que mandas a la cola sea de verdad algo que el cliente no necesita para continuar.
