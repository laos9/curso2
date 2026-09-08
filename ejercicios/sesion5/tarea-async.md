# Tarea de la semana · Sesión 5 (~1.5h)

> Tu Pull Request ya está abierto (o es el mismo de sesiones anteriores). Esta semana solo le sumas commits: cada `git push` a la misma rama se agrega solo.

## 1. Termina tu API (~45 min)

- **Nivel 1 (obligatorio):** el filtro por categoría en `index()` con `?categoria=`, usando tu scope `deCategoria()` de la sesión 2.
- **Nivel 2 (obligatorio):** el endpoint `GET /api/resumen` con totales por categoría, y el `throttle:6,1` en la ruta del token.
- **Nivel 3 (opcional, cuenta como extra):** un `EtiquetaResource`, o que `show` acepte slug además de id.

La guía está en tu proyecto: `ejercicios/sesion5/01-tu-api-rest.md`. Todo lo conceptual de la clase está por escrito en `ejercicios/sesion5/00-apis-por-dentro.md`.

## 2. El administrador manda (~15 min)

Pide un token con `admin@blog.test` y edita por la API un aviso que no sea suyo. Debe responder **200**, no 403, porque el `before()` de tu Policy dice que el admin puede con todo. Si te responde 403, tu Policy no tiene `before()` o el token es del editor.

Deja constancia en el PR: dos líneas diciendo qué respondió cada cuenta sobre el mismo aviso.

## 3. Lo que haya quedado de la sesión 4 (~20 min)

Casi nadie subió su trabajo de Filament, aunque en clase funcionó. Esta semana es el momento: el Resource, el campo `resumen`, el filtro y la acción Publicar. Es entregable de esa sesión y sigue contando. Las guías están en `ejercicios/sesion4/`.

## 4. Una pregunta para traer respondida (~10 min)

Tu blog, tu panel y tu API leen la misma tabla y obedecen la misma `PostPolicy`. Sin embargo, la portada esconde el botón Editar, el panel esconde el botón Editar, y la API responde 403.

Escribe en tu PR, en tres líneas: **qué pasaría si un compañero llamara a tu API con el token de su propio usuario y tratara de borrar un aviso tuyo**, y por qué la respuesta sería la misma aunque le quitaras el `@can` de la tarjeta y el botón del panel.

## 5. Lectura obligatoria: colas y trabajos en segundo plano (~20 min)

`ejercicios/sesion5/03-colas.md`, en tu proyecto. Es el material del módulo de colas, que no cabía en clase. Trae comandos para correr: despachar un trabajo, ver al worker tomarlo, provocar un fallo y reintentarlo.

Al final hay una pregunta corta que va en tu PR: cuál de los endpoints que escribiste hoy se beneficiaría de una cola, y por qué.

## Checklist del Pull Request

Copia esto en la descripción de tu PR y marca lo que cumples:

- [ ] `php artisan install:api` corrido y `routes/api.php` con las rutas de avisos
- [ ] `PostResource` de API: el JSON ya no expone `user_id` ni `created_at` en crudo
- [ ] `store`, `update` y `destroy` con `Gate::authorize` y códigos 201, 200 y 204
- [ ] `HasApiTokens` en `User` y el endpoint `POST /api/token`
- [ ] Rutas de escritura bajo `auth:sanctum`
- [ ] Filtro `?categoria=` (nivel 1)
- [ ] `GET /api/resumen` y `throttle:6,1` en el token (nivel 2)
- [ ] Comprobado: editor sobre aviso ajeno da 403, admin da 200
- [ ] Respondida la pregunta del punto 4
- [ ] Leída la lectura de colas y respondida su pregunta
- [ ] Extra: `EtiquetaResource` o slug en `show` (nivel 3)
- [ ] Extra: lo que faltaba de la sesión 4

## Recordatorio del flujo

```bash
git add -A
git commit -m "sesion 5: API con Sanctum"
git push
```

Si tu PR ya está abierto, con eso basta. Si no lo tienes, ábrelo desde GitHub con base en **tu propio fork** y pega la URL en Moodle.
