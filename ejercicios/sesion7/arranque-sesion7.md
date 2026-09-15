# Antes de la clase · Trae la sesión 7 y córrela

Para qué: dejar tu proyecto listo para la sesión 7. Vas a traer la aplicación de Angular que trae la base del curso, bajar sus dependencias y verla corriendo al lado de tu API de Laravel.

Si puedes, hazlo antes de la clase. Si no, es lo primero que hacemos juntos: es el ritual de inicio.

Tiempo estimado: unos 10 minutos, casi todo esperando la descarga del paso 5.

Todo se escribe en la terminal de tu Codespace (o de tu contenedor), en la carpeta de tu proyecto.

---

## Paso 1 · Quédate en la rama donde está tu API

```bash
git branch
```

La rama marcada con `*` es en la que estás. Tu trabajo de las sesiones 5 y 6 (la API y las pruebas) vive en una rama, y **ahí mismo va a llegar lo nuevo**. No hace falta cambiarte a `main`.

Para confirmar que estás en la correcta:

```bash
ls routes/api.php
```

Si responde `routes/api.php`, estás bien. Si responde `No such file or directory`, cámbiate a la rama donde hiciste la sesión 6 (por ejemplo, `git checkout sesion6`) y vuelve a probar. Si en ninguna de tus ramas existe, sigue en la que estés: en el paso 5 te decimos cómo ponerla.

## Paso 2 · Guarda lo que tengas sin guardar

```bash
git add -A
git commit -m "avance sesion 6"
```

Si responde `nothing to commit, working tree clean`, no había nada pendiente: sigue.

## Paso 3 · Trae lo nuevo del curso

```bash
git fetch upstream
git merge --no-edit upstream/main
```

**Qué debe salir:** una línea `Merge made by the 'ort' strategy.` y la lista de lo que llegó: la carpeta `frontend/`, el script `.devcontainer/preparar-angular.sh` y las guías de `ejercicios/sesion7/`.

Compruébalo:

```bash
ls frontend
```

Debe mostrar, entre otros, `angular.json`, `package.json` y `src`.

## Paso 4 · Súbelo a tu fork

```bash
git push origin HEAD
```

`HEAD` es la rama en la que estás: se sube a tu fork con el mismo nombre.

## Paso 5 · Baja las dependencias de Angular

```bash
bash .devcontainer/preparar-angular.sh
```

La primera vez tarda un par de minutos: baja unos 300 MB. npm va a imprimir avisos de paquetes viejos (`deprecated`); son de las herramientas de Angular 16, no de tu proyecto, y no hay que hacer nada con ellos.

**Qué debe salir al final:** `[instalado] Angular 16.2.12` y las instrucciones para arrancar.

Si antes de esas instrucciones aparece `AVISO: tu proyecto todavia no tiene la API de la sesion 5`, pon la API del curso antes de seguir:

```bash
bash .devcontainer/nivelar-api.sh
```

## Paso 6 · Dos terminales, dos servidores

Hoy corren dos cosas al mismo tiempo, cada una en su terminal:

| Terminal | Dónde | Comando | Qué levanta |
|---|---|---|---|
| 1 | la raíz del proyecto | `composer run dev` | tu API de Laravel, en el puerto 8000 |
| 2 | `frontend/` | `npm start` | tu aplicación de Angular, en el puerto 4200 |

En la terminal 1, como siempre:

```bash
composer run dev
```

Abre una segunda terminal (el botón `+` del panel de terminales) y en ella:

```bash
cd frontend
npm start
```

Espera a que diga `Compiled successfully`. Antes sale un aviso en amarillo, `Warning: This is a simple server for use in testing or debugging`: es esperado. Sale porque el servidor escucha en `0.0.0.0`, que es lo que deja a Codespaces abrir el puerto.

## Paso 7 · Ábrelo en el navegador

- **En Codespaces:** pestaña **PUERTOS** (PORTS), fila del **4200**, ícono del globo ("Abrir en el navegador").
- **En tu contenedor local:** `http://localhost:4200`.

**Qué debe verse:** un encabezado azul que dice **Avisos** y un recuadro punteado. Es tu aplicación de Angular, todavía vacía: en la clase le vas a poner tus avisos.

**La prueba de que Angular alcanza a tu API:** en esa misma dirección del 4200, agrega `/api/avisos` al final. Debe responder el JSON de tus avisos. Esa petición entró por Angular y la atendió tu Laravel.

Listo: llegas a la clase con las dos aplicaciones corriendo.

---

## Si algo falla

| Lo que ves | Qué hacer |
|---|---|
| `fatal: 'upstream' does not appear to be a git repository` | Tu proyecto no conoce el repo del curso. Conéctalo una vez con `git remote add upstream https://github.com/ramsessal/curso2.git` y repite el paso 3 |
| `fatal: Need to specify how to reconcile divergent branches.` | Usaste `git pull`. Usa los dos comandos del paso 3: `git fetch upstream` y `git merge --no-edit upstream/main` |
| `CONFLICT (add/add): Merge conflict in app/Jobs/EnviarAvisoPorCorreo.php` | Es el trabajo que escribiste en la sesión 5 contra el que trae el curso desde la sesión 6. Quédate con el tuyo: `git checkout --ours app/Jobs/EnviarAvisoPorCorreo.php`, después `git add app/Jobs/EnviarAvisoPorCorreo.php` y `git commit --no-edit`. Sigue con el paso 4 |
| `CONFLICT` en cualquier otro archivo | Regresa con `git merge --abort` y avisa en el canal del curso |
| `No encuentro frontend/package.json.` al correr el paso 5 | El paso 3 no se hizo en esta rama. Repítelo aquí |
| `AVISO: en esta carpeta no hay proyecto de Laravel` | Tu proyecto de Laravel no está en esta carpeta. Avisa en el canal del curso |
| El 4200 no aparece en PUERTOS | `npm start` no está corriendo o todavía no dice `Compiled successfully`. Revisa la terminal 2 |
| `/api/avisos` en el 4200 responde 504 | Tu Laravel no está corriendo: `composer run dev` en la terminal 1 |
| La página dice `Invalid Host header` | A tu `angular.json` le falta la línea `allowedHosts`. Tráelo otra vez del curso: `git checkout upstream/main -- frontend/angular.json` |
