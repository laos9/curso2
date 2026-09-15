# Ejercicio 2 · Tu primera pantalla: la lista de avisos

Objetivo: que tu aplicación de Angular muestre los avisos que entrega tu API de Laravel.

Tiempo estimado: 25 minutos.

La explicación de cada pieza (componente, plantilla, servicio, `Observable`, proxy) está en la lectura [`00-angular-por-dentro.md`](00-angular-por-dentro.md). Aquí va el paso a paso.

---

## Antes de empezar: dos servidores, dos terminales

Si todavía no traes la sesión 7 a tu proyecto, primero sigue [`arranque-sesion7.md`](arranque-sesion7.md): trae la base, baja las dependencias y deja las dos terminales corriendo.

Tu proyecto ahora tiene dos aplicaciones: la API de Laravel, que ya conoces, y una aplicación de Angular en la carpeta `frontend/`, que trajo la base de hoy.

| Terminal | Dónde | Comando | Qué levanta |
|---|---|---|---|
| 1 | raíz del proyecto | `composer run dev` | tu API, en el puerto 8000 |
| 2 | `frontend/` | `npm start` | tu aplicación de Angular, en el puerto 4200 |

En la terminal 2:

```bash
cd frontend
npm start
```

Espera a que diga `Compiled successfully` y abre el puerto **4200**: en Codespaces, desde la pestaña PORTS (el ícono del globo) o desde el aviso que aparece abajo a la derecha; en tu contenedor local, `http://localhost:4200`.

Debes ver un encabezado azul que dice **Avisos** y un recuadro punteado: tu aplicación ya arranca.

Al arrancar, Angular imprime un aviso en amarillo: que es un servidor solo para desarrollo. Es esperado.

---

## Paso 1 · Lo que trajo la base

```
frontend/
├── angular.json          la configuración del proyecto (lo que es composer.json + vite.config.js)
├── package.json          las dependencias, con las versiones fijas de Angular 16
├── proxy.conf.json       todo lo que empieza con /api se reenvía a tu Laravel
└── src/
    ├── index.html        la única página HTML; trae una etiqueta <app-root>
    ├── main.ts           arranca la aplicación
    ├── styles.css        los estilos del curso, ya listos
    └── app/
        ├── app.module.ts         el registro de piezas de la aplicación
        ├── app.component.ts      el componente raíz: la clase
        └── app.component.html    el componente raíz: su plantilla
```

`node_modules/` no se toca, igual que `vendor/` en Laravel.

---

## Paso 2 · Tu primer cambio, en vivo

Abre `src/app/app.component.ts` y cambia el valor de `titulo`:

```ts
export class AppComponent {
  titulo = 'Avisos de tu nombre';
}
```

Guarda y mira el navegador: **se actualiza solo**, en uno o dos segundos. En `app.component.html` está escrito `{{ titulo }}`: la plantilla pinta lo que valga la propiedad de la clase. Es la misma idea que `{{ $titulo }}` en Blade, con una diferencia que importa: aquí la pinta el navegador, no el servidor.

---

## Paso 3 · La forma de un aviso

En la terminal 2, detén el servidor con `Ctrl+C` o abre una tercera terminal en `frontend/`, y corre:

```bash
npx ng generate interface modelos/aviso
```

`npx ng generate` es el `php artisan make:` de Angular. Crea `src/app/modelos/aviso.ts`. Déjalo así:

```ts
export interface Aviso {
  id: number;
  titulo: string;
  contenido: string;
  publicado: boolean;
  categoria?: { id: number; nombre: string };
  autor?: string;
  creado: string;
}
```

Son las mismas llaves que decidió tu `PostResource` en la sesión 5. El `?` dice que ese campo puede no venir: tu Resource los manda con `whenLoaded`.

Si detuviste el servidor, vuelve a levantarlo con `npm start`.

---

## Paso 4 · El servicio que habla con tu API

```bash
npx ng generate service servicios/avisos
```

Crea `src/app/servicios/avisos.service.ts`. Déjalo así:

```ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';

import { Aviso } from '../modelos/aviso';

@Injectable({
  providedIn: 'root'
})
export class AvisosService {

  constructor(private http: HttpClient) { }

  listar(): Observable<Aviso[]> {
    return this.http.get<{ data: Aviso[] }>('/api/avisos').pipe(
      map(respuesta => respuesta.data)
    );
  }
}
```

- `constructor(private http: HttpClient)` pide `HttpClient` y lo guarda en `this.http`. Es la promoción de propiedades en el constructor de PHP 8, con otra sintaxis; y quien lo entrega es el inyector de Angular, como el contenedor de servicios de Laravel.
- La dirección es `/api/avisos`, sin dominio ni puerto. La petición va al 4200 y el proxy la reenvía a tu Laravel.
- Tu `PostResource` envuelve la lista en `"data"`. `map` saca la lista de ese sobre.

`HttpClient` hay que habilitarlo en la aplicación. En `src/app/app.module.ts` agrega la importación y el módulo:

```ts
import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { HttpClientModule } from '@angular/common/http';

import { AppComponent } from './app.component';

@NgModule({
  declarations: [
    AppComponent
  ],
  imports: [
    BrowserModule,
    HttpClientModule
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
```

---

## Paso 5 · El componente que la pinta

```bash
npx ng generate component avisos-lista
```

Crea la carpeta `src/app/avisos-lista/` con tres archivos (la clase, la plantilla y su CSS) y **agrega el componente a `app.module.ts` solo**: la terminal lo dice con `UPDATE src/app/app.module.ts`.

La clase, `avisos-lista.component.ts`:

```ts
import { Component, OnInit } from '@angular/core';

import { Aviso } from '../modelos/aviso';
import { AvisosService } from '../servicios/avisos.service';

@Component({
  selector: 'app-avisos-lista',
  templateUrl: './avisos-lista.component.html',
  styleUrls: ['./avisos-lista.component.css']
})
export class AvisosListaComponent implements OnInit {
  avisos: Aviso[] = [];
  cargando = true;
  error = '';

  constructor(private avisosService: AvisosService) { }

  ngOnInit(): void {
    this.cargar();
  }

  cargar(): void {
    this.cargando = true;
    this.avisosService.listar().subscribe({
      next: avisos => {
        this.avisos = avisos;
        this.cargando = false;
      },
      error: () => {
        this.error = 'No pude hablar con tu API. Revisa que composer run dev siga corriendo.';
        this.cargando = false;
      }
    });
  }
}
```

- `ngOnInit()` lo llama Angular una vez, cuando el componente aparece en pantalla.
- `listar()` no devuelve los avisos: devuelve un `Observable`, una respuesta que va a llegar después. `subscribe` dice qué hacer cuando llegue (`next`) y qué hacer si falla (`error`). Mientras tanto, `cargando` vale `true`.

La plantilla, `avisos-lista.component.html` (borra el `<p>avisos-lista works!</p>` que trae):

```html
<p *ngIf="cargando" class="nota">Cargando avisos...</p>
<p *ngIf="error" class="nota mal">{{ error }}</p>

<div class="tarjetas">
  <article *ngFor="let aviso of avisos" class="tarjeta">
    <span class="chip">{{ aviso.categoria?.nombre }}</span>
    <h3>{{ aviso.titulo }}</h3>
    <p>{{ aviso.contenido }}</p>
    <small>{{ aviso.autor }} · {{ aviso.creado | date:'dd/MM/yyyy' }}</small>
  </article>
</div>

<p *ngIf="!cargando && !error && avisos.length === 0" class="vacio">Tu API no tiene avisos publicados.</p>
```

| En Angular | En Blade |
|---|---|
| `*ngIf="cargando"` | `@if ($cargando)` |
| `*ngFor="let aviso of avisos"` | `@foreach ($avisos as $aviso)` |
| `{{ aviso.titulo }}` | `{{ $aviso->titulo }}` |
| `aviso.categoria?.nombre` | `$aviso->categoria?->nombre` |
| `aviso.creado \| date:'dd/MM/yyyy'` | `$aviso->created_at->format('d/m/Y')` |

La fecha llega como texto (JSON no tiene tipo fecha, sesión 5) y el `date` la convierte al mostrarla.

---

## Paso 6 · Ponlo en la página

En `src/app/app.component.html`, cambia el párrafo del recuadro punteado por la etiqueta de tu componente:

```html
<main class="contenido">
  <app-avisos-lista></app-avisos-lista>
</main>
```

La etiqueta `app-avisos-lista` es el `selector` de tu componente. Es el `<x-tarjeta-post>` de tus componentes de Blade.

---

## Checkpoint

- En el 4200 ves **tus avisos en tarjetas**, con su categoría, su autor y su fecha.
- Abre las herramientas del navegador (F12), pestaña **Red** (Network), y recarga: hay una petición `avisos` **a tu propio 4200**, con tu JSON en la respuesta.
- Crea un aviso desde el probador de la sesión 5 y recarga Angular: aparece arriba.

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| La página casi en blanco, y en la consola (F12) `NullInjectorError: No provider for HttpClient!` | Falta `HttpClientModule` en los `imports` de `app.module.ts` (paso 4) |
| `No pude hablar con tu API...` en la página, y en la terminal 2 `[HPM] Error occurred while proxying request ... [ECONNREFUSED]` | Tu Laravel no está corriendo. Levanta `composer run dev` en la terminal 1 |
| `error TS2564: Property 'avisos' has no initializer` | Falta el `= []` en `avisos: Aviso[] = [];` |
| `error TS2532: Object is possibly 'undefined'` en la plantilla | Falta el `?` en `aviso.categoria?.nombre`: la interfaz dice que la categoría puede no venir |
| `error TS2339: Property 'title' does not exist on type 'Aviso'` | Un campo mal escrito. TypeScript lo compara con tu interfaz `Aviso` |
| `'app-avisos-lista' is not a known element` | El componente no está en `declarations` de `app.module.ts`. Con `ng generate` se agrega solo |
| La página del 4200 dice `Invalid Host header` | Tu `angular.json` perdió la línea `allowedHosts`. Vuelve a traer la base del curso |
| Tu API responde 404 en `/api/avisos` | No tienes la API de la sesión 5: en la raíz, `bash .devcontainer/nivelar-api.sh` |
