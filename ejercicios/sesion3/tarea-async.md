# Tarea de la semana · Sesión 3 (~1.5h)

> Tu Pull Request de esta sesión **ya quedó abierto en clase**. Esta semana solo le sumas commits: cada `git push` a la misma rama se agrega solo al PR.

## 1. Termina tu blog protegido (~50 min)

- **Nivel 1 (obligatorio):** `PostPolicy` con `update` y `delete`, `Gate::authorize` en el controlador y `@can` en la tarjeta. La prueba de que quedó: la URL de editar de un aviso ajeno responde **403**.
- **Nivel 2 (obligatorio):** el `before()` que deja pasar al rol admin, con su `null` para no opinar en los demás casos.
- **Nivel 3 (opcional, cuenta como extra):** autorizar `create`, el candado `->can(...)` desde la ruta y una Gate suelta.

## 2. Lo que haya quedado de la sesión anterior (~30 min)

Si tu blog todavía no tiene todo esto, esta semana es el momento; lo de hoy se apoya en ello y lo de la próxima sesión también:

- CRUD completo en `PostController` (crear, editar, borrar con validación) y `web.php` solo enrutando.
- Portada leyendo la base de datos, con el scope `publicados()`.
- Etiquetas con `attach`/`sync` y el archivo `consultas-tinker.md`.

Las guías siguen en tu proyecto, carpeta `ejercicios/sesion2/`.

## 3. Una pregunta para traer respondida (~10 min)

En tu blog, ¿qué pasaría si alguien **sin sesión** manda directamente el formulario de borrar con una herramienta como Postman? Pruébalo y escribe en tu PR, en dos líneas, qué respondió tu aplicación y **cuál** de tus candados lo detuvo. (Pista: tienes tres capas puestas; una sola hizo el trabajo.)

## Checklist del Pull Request

Copia esto en la descripción de tu PR y marca lo que cumples:

```markdown
- [ ] Login funcionando: entro, la barra dice mi nombre, salgo
- [ ] Rutas de escritura protegidas con middleware auth
- [ ] PostPolicy con update y delete basados en el dueño
- [ ] Gate::authorize en edit, update y destroy del controlador
- [ ] @can escondiendo los botones en la tarjeta
- [ ] La URL de editar de un aviso ajeno responde 403
- [ ] before() para el rol admin (nivel 2)
- [ ] Respondida la pregunta de las tres capas
- [ ] Extra: create autorizado, candado en la ruta, Gate suelta (nivel 3)
```

## Recordatorio del flujo

```bash
git add .
git commit -m "Sesion 3: login y policies"
git push
```

Tu PR ya existe: estos commits se le suman solos. Cuando termines, márcalo como **Ready for review** y confirma que la URL esté pegada en la Tarea de Moodle. La rúbrica está publicada: se puntúa el logro, reintentar nunca resta y cuenta tu mejor versión. Cualquier traba: al canal del curso con captura del error.
