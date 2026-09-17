# Antes de la clase · Trae la sesión 8 y córrela

Para qué: llegar a la sesión 8 con tus dos aplicaciones corriendo, la API de Laravel y el frontend de Angular, y con las guías nuevas ya en tu proyecto.

Buena noticia: **hoy no hay que instalar nada**. Angular y sus dependencias ya están en tu proyecto desde la sesión 7. Lo de hoy es traer las guías nuevas y levantar los dos servidores.

Si puedes, hazlo antes de la clase. Si no, es lo primero que hacemos juntos: es el ritual de inicio.

Tiempo estimado: unos 5 minutos.

Todo se escribe en la terminal de tu Codespace (o de tu contenedor), en la carpeta de tu proyecto.

---

## Paso 1 · Quédate en la rama donde está tu trabajo

```bash
git branch
```

La rama marcada con `*` es en la que estás. Tu API de las sesiones 5 y 6 y tu Angular de la sesión 7 viven en una rama, y **ahí mismo va a llegar lo nuevo**. No hace falta cambiarte a `main`.

Para confirmar que estás en la correcta:

```bash
ls frontend/angular.json
```

Si responde `frontend/angular.json`, estás bien. Si responde `No such file or directory`, cámbiate a la rama donde hiciste la sesión 7 (por ejemplo, `git checkout sesion7-tarea`) y vuelve a probar.

## Paso 2 · Guarda lo que tengas sin guardar

```bash
git add -A
git commit -m "avance sesion 7"
```

Si responde `nothing to commit, working tree clean`, no había nada pendiente: sigue.

Este commit importa más que otras veces: en el paso 3 vas a mezclar lo del curso con lo tuyo, y git necesita que lo tuyo ya esté guardado.

## Paso 3 · Trae lo nuevo del curso

```bash
git fetch upstream
git merge --no-edit upstream/main
```

**Qué debe salir:** una línea `Merge made by the 'ort' strategy.` y la lista de lo que llegó: las guías de `ejercicios/sesion8/`.

No se usa `git pull upstream main`: con commits propios en la rama, git se niega a mezclar solo y pide que elijas cómo.

Compruébalo:

```bash
ls ejercicios/sesion8
```

Debe mostrar `arranque-sesion8.md` y `tarea-async.md`.

**Las dos guías que se usan en clase ya las tienes**, desde la sesión pasada: son `ejercicios/sesion7/03-entrar-con-token.md` y `ejercicios/sesion7/04-escribir-y-el-403.md`. La clase de hoy retoma justo ahí, así que están en esa carpeta y no en la de hoy.

## Paso 4 · Súbelo a tu fork

```bash
git push origin HEAD
```

`HEAD` es la rama en la que estás: se sube a tu fork con el mismo nombre.

## Paso 5 · Dos terminales, dos servidores

Igual que la clase pasada, hoy corren dos cosas al mismo tiempo, cada una en su terminal:

| Terminal | Dónde | Comando | Qué levanta |
|---|---|---|---|
| 1 | la raíz del proyecto | `composer run dev` | tu API de Laravel, en el puerto 8000 |
| 2 | `frontend/` | `npm start` | tu aplicación de Angular, en el puerto 4200 |

En la terminal 1:

```bash
composer run dev
```

Abre una segunda terminal (el botón `+` del panel de terminales) y en ella:

```bash
cd frontend
npm start
```

Espera a que diga `Compiled successfully`. Antes sale un aviso en amarillo, `Warning: This is a simple server for use in testing or debugging`: es esperado. Sale porque el servidor escucha en `0.0.0.0`, que es lo que deja a Codespaces abrir el puerto.

**No se corre `preparar-angular.sh`.** Ese script bajó las dependencias en la sesión 7 y ya no hace falta. Si `npm start` responde `sh: ng: not found` o `Cannot find module`, entonces sí: córrelo una vez con `bash .devcontainer/preparar-angular.sh` y vuelve a `npm start`.

## Paso 6 · Ábrelo en el navegador

- **En Codespaces:** pestaña **PUERTOS** (PORTS), fila del **4200**, ícono del globo ("Abrir en el navegador").
- **En tu contenedor local:** `http://localhost:4200`.

**Qué debe verse:** el encabezado **Avisos** y, debajo, la lista de avisos que leíste de tu API en la clase pasada.

Si en la clase pasada no alcanzaste a terminar la lista, vas a ver el recuadro punteado vacío. **No es un problema para hoy**: la sesión 8 empieza en otra pieza, y la lista está paso a paso en la guía [`02-tu-primera-pantalla.md`](../sesion7/02-tu-primera-pantalla.md) para cuando la retomes.

**La prueba de que Angular alcanza a tu API:** en esa misma dirección del 4200, agrega `/api/avisos` al final. Debe responder el JSON de tus avisos. Esa petición entró por Angular y la atendió tu Laravel.

## Paso 7 · Comprueba tus usuarios de práctica

Hoy vas a entrar con usuario y contraseña desde Angular, así que los usuarios tienen que existir en tu base:

```bash
php artisan tinker --execute="echo App\Models\User::count();"
```

Si responde `0`, siémbralos:

```bash
php artisan db:seed --class=UserSeeder
```

Los de práctica son `admin@blog.test` y `editor@blog.test`, los dos con la contraseña `secreto123`.

Listo: llegas a la clase con las dos aplicaciones corriendo y con usuarios para entrar.

---

## Si algo falla

| Lo que ves | Qué hacer |
|---|---|
| `fatal: 'upstream' does not appear to be a git repository` | Tu proyecto no conoce el repo del curso. Conéctalo una vez con `git remote add upstream https://github.com/ramsessal/curso2.git` y repite el paso 3 |
| `fatal: Need to specify how to reconcile divergent branches.` | Usaste `git pull`. Usa los dos comandos del paso 3: `git fetch upstream` y `git merge --no-edit upstream/main` |
| `CONFLICT` en algún archivo | Quédate con lo tuyo: `git checkout --ours <archivo>`, después `git add <archivo>` y `git commit --no-edit`. Si el conflicto es en varios archivos, regresa con `git merge --abort` y avisa en el canal del curso |
| `ls ejercicios/sesion8` responde `No such file or directory` | El paso 3 no llegó a esta rama. Repítelo aquí |
| `sh: ng: not found` al correr `npm start` | Las dependencias no están en esta rama. Córrelas una vez: `bash .devcontainer/preparar-angular.sh` |
| El 4200 no aparece en PUERTOS | `npm start` no está corriendo o todavía no dice `Compiled successfully`. Revisa la terminal 2 |
| La página del 4200 dice `Invalid Host header` | A tu `angular.json` le falta la línea `allowedHosts`. Tráelo otra vez del curso: `git checkout upstream/main -- frontend/angular.json` |
| `/api/avisos` en el 4200 responde 504 | Tu Laravel no está corriendo: `composer run dev` en la terminal 1 |
| `No pude hablar con tu API` en la lista | Lo mismo: la terminal 1 está apagada |
| El 4200 carga pero no se actualiza solo al guardar un archivo | Recarga la página a mano y sigue. En la consola del navegador (F12) se ve si el websocket `ng-cli-ws` no conectó |
| No tienes la API de la sesión 5 en el proyecto | `bash .devcontainer/nivelar-api.sh` y avisa en el canal del curso |
