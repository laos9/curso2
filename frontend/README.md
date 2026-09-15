# frontend · Avisos en Angular 16

La aplicación de Angular de la sesión 7. Consume la API de Laravel de este mismo proyecto.

## Dos servidores, dos terminales

| Terminal | Dónde | Comando | Puerto |
|---|---|---|---|
| 1 | raíz del proyecto | `composer run dev` | 8000, tu API de Laravel |
| 2 | `frontend/` | `npm start` | 4200, tu aplicación de Angular |

Abres la del puerto 4200. Todo lo que empieza con `/api` el servidor de Angular lo reenvía a Laravel: eso lo dice `proxy.conf.json`.

## Instalar las dependencias (una vez)

Desde la raíz del proyecto:

```bash
bash .devcontainer/preparar-angular.sh
```

## Generar piezas

Dentro de `frontend/`:

```bash
npx ng generate component avisos-lista
npx ng generate service servicios/avisos
npx ng generate interceptor interceptores/auth
```

Es el equivalente de `php artisan make:...`.

## Versiones

Angular 16.2, la misma versión de un sistema real en producción. `ng version` marca el Node 22 del contenedor como `Unsupported`, porque Angular 16 se publicó antes que él: es esperado, compila y sirve igual.

`npm start` corre `ng serve`, el servidor de desarrollo de la CLI de Angular. En la 16 trabaja con **webpack**, no con Vite como tu Laravel: empaqueta toda la aplicación antes de servirla y, al guardar, recarga la página completa. Desde Angular 17, los proyectos nuevos usan esbuild y Vite. Cuál usa un proyecto lo dice la línea `"builder"` de `angular.json`.
