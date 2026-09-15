# Ejercicio 1 · Colas: que tu API no espere

Objetivo: vivir el problema de hacer un trabajo lento dentro de la petición, resolverlo con una cola y ver cómo el trabajo avanza en segundo plano. Al final lo rompes a propósito y lo reintentas.

Tiempo estimado: 20 minutos.

La explicación completa de colas está en la lectura de la semana pasada, `ejercicios/sesion5/03-colas.md`. Aquí va el caso concreto, en tu API.

---

## El caso

Cuando alguien crea un aviso, hay que avisarle por correo a cada usuario del blog. Un servidor de correo real tarda por cada mensaje, y con muchos usuarios son minutos.

La base del curso ya te trajo el trabajo que hace ese envío, `app/Jobs/EnviarAvisoPorCorreo.php`. Manda el aviso a cada usuario, uno por uno, y lleva la cuenta en dos columnas nuevas del aviso: `destinatarios` (a cuántos hay que mandarlo) y `notificados` (a cuántos ya les llegó). Para no depender de un servidor de correo, cada envío espera medio segundo.

---

## Paso 0 · Lo que trajo la base

Después del ritual de apertura corre:

```bash
php artisan migrate
php artisan db:seed --class=UsuariosDePracticaSeeder
```

El primero agrega las dos columnas a `posts`. El segundo crea ocho usuarios de práctica: a más destinatarios, más tarda el envío y mejor se ve la cola.

Y deja corriendo `composer run dev`. Uno de sus cuatro procesos es `php artisan queue:listen`: el **worker**, que espera trabajos y los ejecuta. Lleva ahí desde la sesión 1.

---

## Paso 1 · Lee el trabajo

Abre `app/Jobs/EnviarAvisoPorCorreo.php`. Lo importante está en `handle()`:

```php
public function handle(): void
{
    $usuarios = User::all();

    $this->post->destinatarios = $usuarios->count();
    $this->post->notificados = 0;
    $this->post->save();

    foreach ($usuarios as $usuario) {
        usleep(500_000);   // medio segundo por correo, como un servidor real
        Log::info("Aviso {$this->post->id} enviado a {$usuario->email}");
        $this->post->increment('notificados');
    }
}
```

Fíjate en la línea de la clase: `class EnviarAvisoPorCorreo`, a secas. **Todavía no es un trabajo en cola.** Así llega a propósito.

---

## Paso 2 · Que tu API muestre el avance

En `app/Http/Resources/PostResource.php`, agrega dos campos al arreglo de `toArray()`:

```php
'destinatarios' => $this->destinatarios ?? 0,
'notificados' => $this->notificados ?? 0,
```

El `?? 0` es porque un aviso recién creado todavía no trae esas columnas en memoria. Con esto, quien llame a tu API puede ver cómo va el envío.

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

---

## Paso 4 · Siente el problema

Abre en el navegador la página que trajo la base, junto a tu probador:

```
https://<tu-codespace>-8000.app.github.dev/cola-en-vivo.html
```

Si ya pediste un token en el probador, aquí ya está. Si no, pídelo arriba. Luego da clic en **Crear aviso**.

El botón se queda esperando varios segundos y el reloj de la derecha corre. Cuando por fin responde, la tarjeta aparece ya llena: todos los correos salieron **dentro de la petición**, y quien llamó a tu API los esperó uno por uno.

---

## Paso 5 · Arréglalo con dos palabras

En la clase del trabajo, agrega `implements ShouldQueue`:

```php
class EnviarAvisoPorCorreo implements ShouldQueue
```

Vuelve a dar clic en **Crear aviso**:

1. La respuesta sale en décimas de segundo.
2. La tarjeta aparece en **"en la cola"** y a los pocos segundos empieza a llenarse sola, correo por correo.
3. En la terminal de `composer run dev` ves el trabajo en `RUNNING` y luego en `DONE`, y en la columna de logs cada "Aviso enviado a ...".

Ahora da clic en **Crear tres seguidos**. Las tres respuestas salen al instante, y las tarjetas se llenan **una después de otra**: el worker toma los trabajos en el orden en que llegaron. Eso es una cola.

---

## Paso 6 · Rómpelo y reintenta

Los trabajos fallan: el servidor de correo se cae, la red se corta. Provócalo. Pon esta línea al principio de `handle()`:

```php
throw new \RuntimeException('El servidor de correo no responde');
```

Crea un aviso. La respuesta sale rápido (tu API no se enteró de nada), pero la tarjeta se queda sin avanzar y a los pocos segundos se marca como **detenido**. En la terminal de `composer run dev` sale `FAIL`.

El trabajo no se perdió. Está guardado en otra tabla, `failed_jobs`:

```bash
php artisan queue:failed
```

Quita la línea del `throw` y reintenta:

```bash
php artisan queue:retry all
```

La tarjeta vuelve a avanzar sola hasta llenarse. **Un trabajo que falla queda registrado y se puede repetir**; si ese mismo error hubiera tronado dentro de la petición, quien creó el aviso habría visto un 500 y nada más.

---

## Checkpoint

1. Sin `implements ShouldQueue`, crear un aviso tarda varios segundos y la tarjeta aparece llena.
2. Con `implements ShouldQueue`, responde en décimas y la tarjeta se llena sola.
3. Tres seguidos se llenan en orden, uno después del otro.
4. Un trabajo que falla aparece en `php artisan queue:failed` y, al reintentarlo, termina.
5. La línea del `throw` ya no está.

---

## Qué mandar a una cola

La pregunta que decide: **¿quien llamó necesita el resultado para continuar?** Si no lo necesita (un correo, un PDF, un reporte, avisarle a otro sistema), va a la cola. Si la respuesta lo necesita para armarse, va dentro.

Y cuando algo va a la cola, quien llamó merece saber cómo va. Eso es lo que hacen `destinatarios` y `notificados` en tu `PostResource`: responder de inmediato, y dejar que el avance se consulte después.

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| La tarjeta se queda en "en la cola" y nunca avanza | No está corriendo `composer run dev`, que es donde vive el worker. Levántalo |
| Con `implements ShouldQueue`, el botón sigue tardando | Tu `.env` dice `QUEUE_CONNECTION=sync`. Debe decir `database` |
| La tarjeta dice que tu `PostResource` no expone los campos | Falta el paso 2 |
| `no such column: destinatarios` | Falta `php artisan migrate` del paso 0 |
| `Class "App\Jobs\EnviarAvisoPorCorreo" not found` | Falta el `use App\Jobs\EnviarAvisoPorCorreo;` en el controlador |
| La página `cola-en-vivo.html` no existe | No trajiste la base de hoy: repite el ritual de apertura |
| Reintentaste y la tarjeta sigue detenida | Todavía está el `throw` en `handle()`. Quítalo y vuelve a correr `php artisan queue:retry all` |
