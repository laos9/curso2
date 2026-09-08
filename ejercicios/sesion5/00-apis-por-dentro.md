# APIs por dentro

> Es la versión escrita del bloque conceptual de la sesión 5. Sirve antes de la clase para llegar con contexto, durante la clase para no tomar apuntes, y después para releer lo que se pasó rápido.

## 1. Qué cambia cuando del otro lado no hay una persona

Todo lo que has construido hasta hoy asume a alguien con un navegador. Tu portada devuelve HTML porque un navegador sabe pintarlo. Tu formulario tiene `@csrf` porque un navegador manda cookies solas y hay que probar que el envío salió de tu página. Tu login abre una sesión porque el navegador guarda una cookie y la reenvía en cada clic.

Quita a la persona y quita el navegador. Del otro lado ahora hay **otro programa**: una aplicación móvil, un tablero de otra área, un proceso nocturno que sincroniza datos, el sistema de otra dependencia. Ese programa no pinta HTML, no guarda cookies, no llena formularios y no tiene a nadie que lea un mensaje de error en español.

Una **API** es la misma aplicación hablando para ese público. Mismos modelos, misma base de datos, mismas reglas de permisos. Lo que cambia es la forma de la respuesta y la forma de identificarse.

Tres cosas cambian de verdad:

| | Tu blog | Tu API |
|---|---|---|
| Qué devuelve | HTML, listo para pintarse | JSON, listo para procesarse |
| Cómo sabe quién eres | cookie de sesión | token en un encabezado |
| Cómo avisa que algo salió mal | vuelve al formulario con `@error` | código de estado más JSON con el detalle |

## 2. JSON, el formato en el que se ponen de acuerdo

Lo primero que conviene desarmar: **JSON es texto**. No es un objeto, no es una estructura de datos, no es un archivo especial. Es una cadena de caracteres con una forma acordada que cualquier lenguaje sabe leer y escribir.

Tu arreglo de PHP:

```php
[
    'id' => 3,
    'titulo' => 'Cambio de horario',
    'publicado' => true,
    'resumen' => null,
    'etiquetas' => ['turnos', 'operativo'],
    'categoria' => ['nombre' => 'Aviso'],
]
```

El mismo dato, como JSON:

```json
{
  "id": 3,
  "titulo": "Cambio de horario",
  "publicado": true,
  "resumen": null,
  "etiquetas": ["turnos", "operativo"],
  "categoria": { "nombre": "Aviso" }
}
```

Casi idéntico, y no es casualidad: el arreglo asociativo de PHP y el objeto de JSON son la misma idea. Las diferencias son de escritura: comillas dobles obligatorias en las llaves, dos puntos en vez de `=>`, y nada de coma final.

**En JSON solo existen seis cosas:**

| En JSON | En PHP |
|---|---|
| `"texto"` | `string` |
| `42`, `3.5` | `int`, `float` |
| `true`, `false` | `bool` |
| `null` | `null` |
| `[ ... ]` | arreglo con índices |
| `{ ... }` | arreglo asociativo |

Y nada más. **No hay tipo fecha, ni dinero, ni tu modelo `Post`.** Por eso las fechas viajan como texto en un formato acordado (`"2026-09-08T14:30:00+00:00"`) y quien recibe decide si lo convierte a su propio tipo de fecha. Lo mismo pasa con los decimales de dinero: viajan como número o como texto, y el acuerdo lo pones tú.

Ganó sobre alternativas más viejas como XML por dos razones prácticas: pesa menos porque no repite el nombre de cada campo al cerrarlo, y era directamente ejecutable en JavaScript, que es el lenguaje del navegador. Hoy todos los lenguajes lo leen y lo escriben con una línea.

## 3. El viaje de un dato, de tu tabla a la pantalla de otro

Este es el recorrido completo, y conviene tenerlo claro porque explica por qué el API Resource importa tanto.

1. **Una fila** en la tabla `posts`. Es lo que hay en la base de datos.
2. **Un objeto `Post`** de Eloquent, en PHP. Trae relaciones, scopes, accessors y métodos. Es lo que manipulas en tu controlador.
3. **Un arreglo**, el que devuelve tu `PostResource`. Aquí decides qué campos salen y con qué nombre.
4. **Texto.** Laravel llama a `json_encode` sobre ese arreglo y produce una cadena. Nada más.
5. **Viaja por HTTP** dentro del cuerpo de la respuesta, con el encabezado `Content-Type: application/json`.
6. **Del otro lado se reconstruye** en el tipo del lenguaje que llamó: un objeto de JavaScript, un `struct` de Swift, un `data class` de Kotlin, un diccionario de Python.

Los pasos 1, 2 y 3 son exactamente lo que ya hace tu portada, hasta el momento de pintar la tarjeta.

**En el paso 4 se pierde todo.** Los tipos de PHP, los métodos del modelo, las relaciones que cargaste, la clase misma: nada de eso cruza. Solo queda una cadena de texto. Tu `Post` no llega nunca al otro lado; llega su retrato.

Por eso el paso 3 es el que importa: es lo último que decide **qué sobrevive al viaje**. Si olvidas un campo ahí, del otro lado no existe. Si dejas salir uno de más, del otro lado alguien lo va a usar y ya no lo vas a poder quitar sin romperle su aplicación.

## 4. Quién llama a tu API

Esta es la pregunta que le da sentido a todo lo anterior. Los cuatro casos típicos, con el código real de cada uno pidiendo lo mismo:

**Un frontend en el navegador.** Angular, React o Vue: la pantalla vive aparte de tu servidor y solo pide datos.

```javascript
const r = await fetch('/api/avisos', { headers: { 'Accept': 'application/json' } });
const { data } = await r.json();
```

**Una aplicación de Android**, en Kotlin. Se instala en el teléfono, pero los datos siguen viviendo en tu servidor.

```kotlin
val r = client.get("$base/api/avisos") { header("Accept", "application/json") }
val avisos: Respuesta = r.body()
```

**Una aplicación de iPhone**, en Swift. Otro lenguaje, otro sistema operativo, la misma dirección.

```swift
let (data, _) = try await URLSession.shared.data(from: url)
let avisos = try JSONDecoder().decode(Respuesta.self, from: data)
```

**Otro sistema o un proceso automatizado**, aquí en Python. Un servicio nocturno que sincroniza, un tablero de otra área, el sistema de otra dependencia.

```python
r = requests.get(f"{base}/api/avisos", headers={"Accept": "application/json"})
avisos = r.json()["data"]
```

Los cuatro hacen lo mismo: mandan un `GET`, piden JSON y convierten el texto que llega a su propio tipo de dato.

**Tu API no sabe ni le importa quién la llamó.** Esa es la ventaja: escribes una vez y sirves a todos. Y es también la razón de que el contrato importe tanto: si cambias el nombre de una llave, rompes a los cuatro a la vez, y ninguno se entera hasta que truena.

Es también el patrón que se usa cuando el frontend y el backend están escritos en lenguajes distintos, que es más común de lo que parece: un frontend en TypeScript hablando con un backend en PHP, o en Python, o en Java. JSON es lo único que los dos entienden.

## 5. HTTP ya lo sabías

No hay nada nuevo en el transporte. Tus formularios ya mandan `POST`, tus enlaces ya son `GET`, tu botón de borrar ya manda `DELETE` disfrazado con `@method('DELETE')`. Una API usa los mismos verbos, sin disfraz:

| Verbo | Qué significa | En tu blog |
|---|---|---|
| `GET` | dame esto, no cambies nada | ver la portada |
| `POST` | crea algo nuevo | guardar un aviso |
| `PUT` | reemplaza este registro | actualizar un aviso |
| `DELETE` | bórralo | borrar un aviso |

La diferencia práctica es que **el verbo carga significado**. En tu blog, `POST /avisos` crea y `POST /avisos/7` actualiza, porque los navegadores solo saben mandar `GET` y `POST`. En una API el verbo dice la intención sin ambigüedad, y por eso las direcciones se vuelven cortas: `/api/avisos` con cuatro verbos distintos hace lo que tus siete rutas hacían.

## 6. Los códigos de estado son el idioma común

Cuando una persona ve un error, lee. Cuando un programa recibe un error, tiene que **decidir** qué hacer: reintentar, pedir credenciales, avisar al usuario, abortar. Por eso el código de estado importa tanto en una API y tan poco en un blog.

| Código | Qué significa | Cuándo lo vas a ver |
|---|---|---|
| `200` | salió bien, aquí están los datos | leer, actualizar |
| `201` | se creó | un `POST` que da de alta |
| `204` | salió bien y no hay nada que devolver | un borrado |
| `401` | no sé quién eres | falta el token o ya no sirve |
| `403` | sé quién eres y no te toca | tu Policy dijo que no |
| `404` | eso no existe | id equivocado |
| `422` | los datos no pasaron la validación | falta el título |
| `429` | vas demasiado rápido | límite de peticiones |
| `500` | tronó mi código | tu error, no el suyo |

La distinción que más se equivoca: **401 es "no sé quién eres" y 403 es "sé quién eres y no te toca"**. La primera se arregla con credenciales; la segunda no se arregla, es una decisión.

## 7. REST, en una frase honesta

REST es un estilo, no una tecnología ni un paquete. En la práctica diaria se reduce a tres acuerdos: las direcciones nombran **cosas** y no acciones (`/api/avisos`, no `/api/obtenerAvisos`), el **verbo** dice qué hacer con esa cosa, y cada petición trae todo lo necesario para entenderse sola, sin depender de peticiones anteriores.

Ese último punto se llama "sin estado" y es el que explica los tokens. El servidor no recuerda que ya te identificaste hace un minuto; cada petición trae su propia credencial.

## 8. `routes/api.php`, el archivo que no existe

Abre `routes/` en tu proyecto. Hay `console.php` y `web.php`. **No hay `api.php`**, porque Laravel 12 no lo trae de fábrica: si tu aplicación no expone una API, ese archivo sería peso muerto.

```bash
php artisan install:api
```

Ese comando crea `routes/api.php`, agrega la línea que lo registra en `bootstrap/app.php`, instala Sanctum y corre la migración de la tabla de tokens. Al terminar imprime un aviso que es tarea pendiente: agregar el trait `HasApiTokens` a tu modelo `User`.

Lo que declares en ese archivo:

- cuelga de `/api` automáticamente, sin que escribas el prefijo,
- **no** pasa por sesión ni por cookies,
- **no** tiene protección CSRF, porque no hay formularios que proteger.

## 9. El API Resource es el componente Blade de la API

Si tu controlador devuelve un modelo, Laravel lo convierte a JSON solo. Funciona, y es una mala idea:

```json
{ "id": 3, "titulo": "...", "categoria_id": 2, "user_id": 5,
  "created_at": "2026-09-01T10:00:00.000000Z", "updated_at": "..." }
```

Estás publicando tu tabla tal cual. El día que agregues una columna aparece en la API sin que nadie lo decida, y el día que renombres una, rompes a todos los que la consumen.

Un **API Resource** es una clase que decide qué sale y con qué forma:

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'titulo' => $this->titulo,
        'categoria' => $this->whenLoaded('categoria', fn () => [
            'id' => $this->categoria->id,
            'nombre' => $this->categoria->nombre,
        ]),
        'autor' => $this->whenLoaded('user', fn () => $this->user?->name),
        'creado' => $this->created_at->toIso8601String(),
    ];
}
```

**La comparación que ordena todo:** este archivo es el `tarjeta-post.blade.php` de la API. Tu componente Blade decide qué ve el navegador; el API Resource decide qué ve otro programa. Mismo modelo, dos presentaciones.

Tres decisiones que están ahí:

- `whenLoaded` solo incluye la relación si el controlador la cargó con `with()`. Sin eso, cada aviso dispararía una consulta por su categoría: el N+1 de la sesión 2, ahora en la API.
- `autor` manda el nombre, no el objeto `User` completo, que trae correo y contraseña hasheada.
- `creado` va en formato estándar, no en `d/m/Y`. Quien consume decide cómo mostrar la fecha.

## 10. Paginar sin enlaces

`paginate(10)` en tu portada pinta botones de páginas. En una API el mismo método agrega dos llaves al JSON:

```json
{ "data": [ ... ],
  "links": { "first": "...", "next": "...", "prev": null },
  "meta": { "current_page": 1, "total": 24, "per_page": 10 } }
```

Nadie pinta nada: quien consume decide si hace botones, scroll infinito o nada. Los mismos datos, otra presentación.

## 11. Cómo se identifica un programa

Tu blog te reconoce por una cookie de sesión: entras una vez, el navegador guarda la cookie y la manda sola. Un programa no tiene navegador ni cookies, así que se identifica con un **token**: una cadena larga que manda en cada petición, en un encabezado.

```
Authorization: Bearer 7|kL9xR2mNp4qW8vT1sY6bC3dF5gH0jK
```

Un token es una contraseña, con dos ventajas: se puede tener uno por dispositivo, y se puede revocar uno sin tocar los demás.

**Sanctum** es el paquete de Laravel que hace eso. `install:api` ya lo instaló. Lo que aporta:

- el trait `HasApiTokens` en `User`, que da `createToken()` y `tokens()`,
- la tabla `personal_access_tokens`, donde vive el **hash** del token, no el token,
- el guardia `auth:sanctum`, que revisa el encabezado y carga al usuario.

Cuando llamas `createToken('mi telefono')`, la propiedad `plainTextToken` es **la única vez** que el token se ve completo. Después solo existe su hash. Si el usuario lo pierde, se genera otro.

## 12. El encabezado que explica el error más común

Una ruta protegida sin token debería responder `401`. Con `curl` a secas responde `302` y te manda al login de tu blog.

No es un error de Laravel. El middleware de autenticación tiene que decidir entre responder un error o mandarte a iniciar sesión, y decide **según lo que pediste**:

- si la petición trae `Accept: application/json`, responde `401` en JSON,
- si no trae nada, asume que hay un navegador del otro lado y redirige.

**Regla práctica: si te llega HTML donde esperabas JSON, te faltó ese encabezado.** El probador del curso lo manda siempre, por eso ahí no lo ves.

## 13. La misma Policy, tres caras

Tu `PostPolicy` de la sesión 3 no cambia ni una línea para gobernar la API. `Gate::authorize('update', $post)` en un controlador de API responde `403` en JSON.

Eso significa que una regla escrita una vez gobierna tres lugares que no se conocen entre sí:

| Dónde | Qué hace la Policy |
|---|---|
| Tu portada | el `@can` esconde el botón Editar, y `Gate::authorize` corta la petición |
| El panel de Filament | Filament pregunta antes de pintar el botón, y `EditRecord` corta la URL escrita a mano |
| Tu API | `Gate::authorize` responde 403 en JSON |

Es la razón por la que la autorización se pone en el modelo de permisos y no en la pantalla: **las pantallas cambian, la regla no**.

## 14. El viaje completo de una petición, caso por caso

Junta todo lo anterior. Una petición a tu API recorre ocho piezas, siempre en el mismo orden, y lo interesante es **dónde se detiene cada caso**:

`quien llama` → `routes/api.php` → `auth:sanctum` → `PostController` → `Gate` y tu `PostPolicy` → `Eloquent` → `PostResource` → `respuesta`

**Caso 1: `GET /api/avisos`.** Se salta el guardia y la Policy, porque leer avisos publicados no pide token ni dueño. Eloquent consulta, el Resource recorta y sale JSON. Termina en **200**.

**Caso 2: `POST /api/avisos` sin token.** Se detiene en `auth:sanctum`. Tu controlador nunca se ejecutó, tu validación nunca corrió y la base de datos ni se enteró. Termina en **401**.

**Caso 3: `POST /api/avisos` con token.** El recorrido completo: el token identifica, la validación aprueba, la Policy permite crear, Eloquent guarda y el Resource arma la respuesta. Termina en **201**.

**Caso 4: `PUT /api/avisos/1` sobre un aviso ajeno, con token válido.** Pasa el guardia (sí sabe quién eres) y pasa el controlador, y **se detiene en tu Policy**. Termina en **403**.

Ese cuarto caso merece una segunda lectura: **la respuesta salió sin tocar la base de datos**. La autorización no es un filtro sobre el resultado, es una decisión que ocurre antes de consultar. Por eso da lo mismo que quien llama sea una app, un frontend o `curl`: la regla está del lado del servidor, y del lado del servidor está escrita una sola vez.

## 15. Tres agujeros propios de una API

Una API es la puerta más expuesta de un sistema: no tiene pantalla que disimule y quien la llama no respeta tus reglas por cortesía.

**El token filtrado.** Un token es una contraseña. Si se va en un commit, en una captura o en un mensaje, quien lo tenga es tú. Por eso los tokens llevan nombre de dispositivo y por eso existe un endpoint para revocarlos.

**La asignación masiva.** `Post::create($datos)` guarda lo que llegó del cliente. Si `user_id` está en tu `$fillable` y no lo sobreescribes después de validar, alguien puede mandar `"user_id": 1` y crear un aviso a nombre de otro. Lo que te protege son dos cosas: el `$fillable` del modelo y asignar tú el dueño después de validar.

**Sin límite de peticiones.** Nada impide pedir un token diez mil veces por minuto para adivinar una contraseña. Laravel trae el freno:

```php
Route::post('/token', [TokenController::class, 'crear'])->middleware('throttle:6,1');
```

Seis por minuto por dirección; al pasarse responde `429`.

## 16. Por qué el navegador no puede llamar a cualquier API

Si abres una página y esa página llama por JavaScript a un servidor distinto, el navegador lo bloquea salvo que el servidor de destino diga expresamente que lo permite. Se llama **política del mismo origen**, y existe para que una página cualquiera no pueda leer, con tus cookies, la información de tu banco.

El permiso se da con una cabecera: `Access-Control-Allow-Origin`. Laravel la manda para las rutas `/api/*` de fábrica.

En Codespaces hay una capa más: el túnel que publica tu puerto bloquea las llamadas de otro origen **antes** de que lleguen a tu Laravel. Por eso el probador del curso vive dentro de tu propio proyecto, en `public/probador-api.html`: es el mismo origen que tu API y no hay nada que negociar. Si lo apuntas a la API de un compañero, va a fallar en el navegador y va a funcionar desde la terminal, porque `curl` no es un navegador y no aplica esa política.

## Glosario

- **API**: la aplicación hablando para otros programas en vez de para personas.
- **REST**: estilo donde las direcciones nombran cosas, el verbo dice qué hacer y cada petición se entiende sola.
- **JSON**: el formato de texto en el que viajan los datos. Solo tiene seis tipos: texto, número, booleano, nulo, lista y objeto.
- **Endpoint**: una dirección concreta de la API, con su verbo.
- **Código de estado**: el número con el que la respuesta dice cómo le fue.
- **API Resource**: la clase que decide qué campos salen y con qué forma.
- **Token**: cadena que identifica a quien llama, en lugar de una sesión con cookie.
- **Sanctum**: el paquete de Laravel que emite y valida esos tokens.
- **`auth:sanctum`**: el middleware que exige token válido.
- **CORS**: el permiso que un servidor da para que páginas de otro origen lo llamen desde el navegador.
- **Throttle**: el límite de peticiones por minuto.
