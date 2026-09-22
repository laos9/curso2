# Antes de la clase · Trae la sesión 9 y prepara Python

Para qué: llegar a la sesión 9 con Python y Django ya instalados en tu proyecto, para que la clase empiece escribiendo código y no esperando descargas.

Aviso: **hoy sí hay que instalar algo**, y por eso conviene hacerlo antes. Es poco, unos 15 segundos medidos en el contenedor del curso, pero multiplicado por todo el grupo a la vez y con una red compartida se convierte en un rato perdido.

Lo que vas a instalar es pequeño: Django y su REST Framework, dentro de una carpeta propia de tu proyecto. Nada de esto toca tu Laravel ni tu Angular.

Tiempo estimado: unos 3 minutos.

Todo se escribe en la terminal de tu Codespace (o de tu contenedor), en la carpeta de tu proyecto.

---

## Paso 1 · Quédate en la rama donde está tu trabajo

```bash
git branch
```

La rama marcada con `*` es en la que estás. Tu API de las sesiones 5 y 6 y tu Angular de las sesiones 7 y 8 viven en una rama, y **ahí mismo va a llegar lo nuevo**. No hace falta cambiarte a `main`.

Para confirmar que estás en la correcta:

```bash
ls routes/api.php
```

Si responde `routes/api.php`, estás bien. Si responde `No such file or directory`, cámbiate a la rama donde hiciste la sesión 5 y vuelve a probar.

## Paso 2 · Guarda lo que tengas sin guardar

```bash
git add -A
git commit -m "avance sesion 8"
```

Si responde `nothing to commit, working tree clean`, no había nada pendiente: sigue.

## Paso 3 · Trae lo nuevo del curso

```bash
git fetch upstream
git merge --no-edit upstream/main
```

**Qué debe salir:** una línea `Merge made by the 'ort' strategy.` y la lista de lo que llegó: la carpeta `api-django/` y las guías de `ejercicios/sesion9/`.

No se usa `git pull upstream main`: con commits propios en la rama, git se niega a mezclar solo y pide que elijas cómo.

Compruébalo:

```bash
ls api-django/manage.py
```

Si responde `api-django/manage.py`, ya lo tienes.

## Paso 4 · Prepara Python

```bash
bash api-django/preparar-django.sh
```

Esto hace cuatro cosas: revisa que haya Python, instala el módulo `venv` si falta, crea un entorno virtual en `api-django/.venv` e instala Django y DRF ahí dentro.

**Qué debe salir al final:**

```
    Django 4.2.x
    DRF    3.15.x
    Migraciones aplicadas (SQLite, en api-django/db.sqlite3)
```

Si te pide contraseña al usar `sudo`, déjalo correr: en Codespaces no la pide.

**Por qué un entorno virtual.** Es lo mismo que `vendor/` en tu Laravel: una carpeta con las dependencias de este proyecto y de ningún otro. En Python no es automático, hay que crearla. Por eso los comandos de Django llevan `.venv/bin/python` por delante.

## Paso 5 · Levanta la API de Django y compruébala

```bash
cd api-django
.venv/bin/python manage.py runserver 0.0.0.0:8001
```

Queda en el **puerto 8001**, para no pelearse con tu Laravel en el 8000.

En otra terminal:

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8001/
```

**Qué debe salir:** `200`.

Si abres esa dirección en el navegador vas a ver la portada de bienvenida de Django, con un cohete. Eso significa que el framework corre, y es todo lo que necesitas hoy.

Todavía **no hay ninguna ruta de API**: `/api/avisos/` responde 404 a propósito. El modelo, el serializer y las rutas los escribes tú mañana en clase. Los archivos vienen casi vacíos justamente para eso.

Para detener el servidor, `Ctrl+C`.

## Paso 6 · Avisa en el canal

Escribe **listo 9** en el canal cuando el paso 5 te haya dado el 200.

Si algo se atoró, escribe en qué paso y pega lo que te salió. Es mejor resolverlo hoy que mañana con el reloj corriendo.

---

## Si algo sale mal

| Qué te sale | Qué hacer |
|---|---|
| `No such file or directory` en el paso 3 | El merge no trajo la carpeta. Revisa que el paso 3 haya dicho `Merge made by...` y no `Already up to date.` |
| `sudo: command not found` | No estás en el contenedor del curso, sino en tu máquina. Abre el Codespace o el contenedor. |
| El paso 4 se queda callado un rato | Normal: está descargando. En el contenedor del curso tarda unos 15 segundos; con red lenta, un par de minutos. |
| `Address already in use` en el paso 5 | Ya tienes algo en el 8001. Usa `runserver 0.0.0.0:8002` y ajusta el `curl`. |
| `404` en el paso 5 | Estás pidiendo una ruta que todavía no existe. La que responde hoy es `http://localhost:8001/api-auth/login/` |

---

## Si quieres llegar con ventaja

La lectura [`00-django-por-dentro.md`](00-django-por-dentro.md) es la versión escrita de la clase, y sus **seis primeras secciones están pensadas para leerlas antes**: lo justo de Python para leer Django, el entorno virtual a fondo, qué es cada archivo del proyecto, la diferencia entre proyecto y app, `settings.py` sección por sección y de dónde sale la configuración que cambia entre máquinas.

No es obligatorio y no son más de veinte minutos. Si las lees, la clase deja de ser un desfile de archivos nuevos.
