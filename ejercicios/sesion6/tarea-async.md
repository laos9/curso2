# Tarea de la semana · Sesión 6

Tiempo estimado: 1.5 horas. Se entrega en el Pull Request de siempre.

---

## 1. Termina la suite de tu API (45 min)

Las once pruebas de la guía [`03-probar-la-api.md`](03-probar-la-api.md), en `tests/Feature/Api/AvisosApiTest.php`:

- [ ] el listado responde 200 y trae solo los publicados
- [ ] cada aviso trae los campos que decidió el `PostResource`
- [ ] sin token responde 401
- [ ] con token crea el aviso, responde 201 y deja el correo en la cola
- [ ] el aviso creado queda con el `user_id` de quien lo mandó
- [ ] sin título responde 422 y se queja de los campos correctos
- [ ] editar un aviso ajeno responde 403 y no lo cambia
- [ ] editar el propio responde 200
- [ ] un admin sí puede con el ajeno
- [ ] credenciales correctas devuelven token, incorrectas responden 422 sin dejar token
- [ ] `GET /api/yo` no publica la tabla `users`

Y las cinco unitarias de tu `PostPolicy` (paso 4 de la guía), en `tests/Unit/PostPolicyTest.php`.

Meta: `php artisan test` en verde, sin saltarte ninguna.

---

## 2. Una prueba tuya (20 min)

Escribe **una prueba que no esté en la guía**, sobre algo que decidiste tú.

Ideas, por si no se te ocurre:

- El filtro `?categoria=` devuelve solo los avisos de esa categoría.
- `GET /api/avisos/{id}` de un aviso que no existe responde 404.
- El `throttle` responde 429 cuando pasas del límite.
- `POST /api/token/revocar` deja el token inservible para la siguiente petición.
- Un usuario con rol `lector` no puede crear avisos.

Regla para elegir: si la respuesta a "¿esto lo decidí yo?" es sí, sirve.

---

## 3. Rompe tu código a propósito (15 min)

Esta parte es la que enseña de verdad.

1. Elige un cambio destructivo de una línea. Por ejemplo, quitar el `Gate::authorize('update', $post)` del `update()` de tu `Api/PostController`.
2. Corre `php artisan test`.
3. Anota **cuál prueba se puso roja** y qué decía exactamente.
4. Deshaz el cambio y comprueba que vuelve a estar todo en verde.

Mira también tus unitarias de la Policy: ¿se pusieron rojas o no? Escribe por qué en una línea.

En la descripción del PR escribe esas tres líneas:

```
Rompi:      quite el Gate::authorize del update()
Se puso roja: "editar un aviso ajeno responde 403"
El mensaje:  Expected response status code [403] but received 200.
```

Si al romper algo **ninguna prueba se pone roja**, ese es el hallazgo importante: tienes un hueco. Escríbelo también, vale igual.

---

## 4. Antes de la sesión 7 (10 min)

La sesión 7 es Angular, y la aplicación que vas a escribir consume **tu** API de la sesión 5. Llega con la API respondiendo y con `php artisan test` en verde: si algo falla al conectar el frontend, tus pruebas te dicen si el problema está de este lado o del otro. No hay que instalar nada antes.

---

## Cómo se entrega

```bash
git add -A
git commit -m "sesion 6: pruebas de la API"
git push origin HEAD
```

Y en Moodle, la URL de tu Pull Request en **Entrega Sesión 6**.

En la descripción del PR van tres cosas:

1. Cuántas pruebas y cuántas aserciones te reporta `php artisan test`.
2. Cuál fue tu prueba propia y por qué la elegiste.
3. Las tres líneas del punto 3.

---

## Checklist de la entrega

- [ ] `tests/Feature/Api/` con tus pruebas
- [ ] `implements ShouldQueue` en `EnviarAvisoPorCorreo`, su `dispatch()` en el `store()` de la API, y `destinatarios` y `notificados` en tu `PostResource` (ejercicio 1)
- [ ] `database/factories/PostFactory.php` y `CategoriaFactory.php`
- [ ] `use HasFactory;` en `Post` y en `Categoria`
- [ ] `tests/Unit/PostPolicyTest.php` con las unitarias de tu Policy
- [ ] `php artisan test` en verde
- [ ] la descripción del PR con los tres puntos de arriba

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| `no such table: posts` | Falta `uses(RefreshDatabase::class);` en el archivo |
| `Call to undefined method ...::factory()` | Falta `use HasFactory;` en el modelo |
| El 401 te llega como 302 | Usaste `$this->post()` en vez de `$this->postJson()` |
| `Command "pest:install" is not defined` | Ese comando no existe, usa `./vendor/bin/pest --init` |
| Una prueba pasa sola pero falla junto con las demás | Algo se quedó guardado entre pruebas. Comprueba que el archivo tenga `uses(RefreshDatabase::class)` y que no estés usando datos creados en otra prueba |
| No tengo la API de la sesión 5 | `bash .devcontainer/nivelar-api.sh` |
