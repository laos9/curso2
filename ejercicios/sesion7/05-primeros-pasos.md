# Ejercicio 5 · Primeros pasos: una pieza a la vez

Para quién es: si en clase alguna pieza se te fue, o quieres afianzarlas antes de la tarea. Todo pasa en un componente de práctica, aparte de tu lista y tus formularios, así que no rompes lo que ya funciona. No necesita token.

Tiempo estimado: unos 40 minutos. Cada paso dice qué escribir, qué debe verse y a qué slide de la clase corresponde. Si ya te sientes con soltura, el siguiente nivel está en [`06-retos-avanzados.md`](06-retos-avanzados.md).

Antes de empezar: `npm start` corriendo en `frontend/` (terminal 2). Para el paso 9, también `composer run dev` (terminal 1).

---

## Paso 1 · Un componente para practicar

Dentro de `frontend/`:

```bash
npx ng generate component practica
```

```
CREATE src/app/practica/practica.component.css (0 bytes)
CREATE src/app/practica/practica.component.html (23 bytes)
CREATE src/app/practica/practica.component.ts (210 bytes)
UPDATE src/app/app.module.ts (1519 bytes)
```

La CLI crea los tres archivos y **agrega el componente a `app.module.ts`**: es la línea `UPDATE` (el número de bytes depende de cómo esté tu módulo). No hay que declararlo a mano.

Ponlo en la página. En `src/app/app.component.html`, arriba de `<app-entrar>`:

```html
<app-practica></app-practica>
```

**Qué debe verse:** arriba de todo, `practica works!`. Es la plantilla que escribió la CLI.

## Paso 2 · Pintar datos de la clase

En `practica.component.ts`, deja la clase así (el `import` y el `@Component` no cambian):

```ts
export class PracticaComponent {
  nombre = 'Guardia nocturna';
  turnos = 3;
}
```

Y cambia todo `practica.component.html` por:

```html
<section class="tarjeta">
  <h3>Hola, {{ nombre }}</h3>
  <p>Te tocan {{ turnos }} turnos: {{ turnos * 8 }} horas.</p>
</section>
```

**Qué debe verse:** `Hola, Guardia nocturna` y `Te tocan 3 turnos: 24 horas.` Dentro de `{{ }}` va una expresión: `turnos * 8` se calcula. Es la interpolación, la primera de las cuatro formas del slide 28.

## Paso 3 · Un botón que cambia un dato

En la clase, debajo de `turnos`:

```ts
  sumar(): void {
    this.turnos++;
  }
```

En la plantilla, debajo del párrafo:

```html
  <button (click)="sumar()">Un turno más</button>
```

**Qué debe verse:** cada clic sube los turnos y las horas, sin recargar. Nadie le dijo a Angular que volviera a pintar: es la detección de cambios del slide 31. `(click)` es otra de las cuatro formas: la pantalla llama a la clase.

## Paso 4 · Mostrar u ocultar

Debajo del botón:

```html
  <p *ngIf="turnos > 5" class="nota mal">Son demasiados turnos.</p>
```

**Qué debe verse:** con tres clics (de 3 a 6 turnos) aparece `Son demasiados turnos.` Recarga la página: vuelve a 3 y el mensaje se va. El dato vivía en la memoria del navegador, no en tu API. (Slide 29.)

## Paso 5 · Una lista

En la clase:

```ts
  pendientes = ['Revisar radios', 'Entregar reporte', 'Cambiar llantas'];
```

En la plantilla, debajo del mensaje:

```html
  <ul>
    <li *ngFor="let pendiente of pendientes; let i = index">{{ i + 1 }}. {{ pendiente }}</li>
  </ul>
```

**Qué debe verse:** `1. Revisar radios`, `2. Entregar reporte` y `3. Cambiar llantas`. `let i = index` da la posición, que empieza en 0: por eso el `i + 1`.

## Paso 6 · Escribir en un campo

En la clase:

```ts
  nuevo = '';

  agregar(): void {
    this.pendientes.push(this.nuevo.trim());
    this.nuevo = '';
  }
```

En la plantilla, debajo de la lista:

```html
  <input [(ngModel)]="nuevo" placeholder="Nuevo pendiente">
  <button (click)="agregar()" [disabled]="!nuevo.trim()">Agregar</button>
```

**Qué debe verse:** `Agregar` está desactivado mientras el campo está vacío. Escribe `Revisar extintores` y pulsa `Agregar`: aparece como el 4 y el campo se limpia solo, porque `agregar()` dejó `nuevo` en `''` y `[(ngModel)]` llevó ese valor de regreso al campo.

Con este paso ya usaste las cuatro formas del slide 28: `{{ }}`, `[disabled]`, `(click)` y `[(ngModel)]`. `[(ngModel)]` necesita `FormsModule`, que tu módulo trae desde el ejercicio 3.

## Paso 7 · Pipes

En la clase:

```ts
  hoy = new Date();
```

En la plantilla, al final:

```html
  <p>{{ nombre | uppercase }} · {{ hoy | date:'dd/MM/yyyy' }}</p>
```

**Qué debe verse:** `GUARDIA NOCTURNA · ` y la fecha de hoy. El pipe cambia cómo se pinta, no el dato: arriba, `Hola, Guardia nocturna` sigue igual. (Slide 29.)

## Paso 8 · Tres errores, a propósito

Haz cada cambio, lee la terminal de `npm start` y regrésalo antes del siguiente:

| Cambio | Lo que dice la terminal |
|---|---|
| `{{ nombres }}` en lugar de `{{ nombre }}` | `error TS2551: Property 'nombres' does not exist on type 'PracticaComponent'. Did you mean 'nombre'?` |
| `<p [valor]="nombre">` en el primer párrafo | `error NG8002: Can't bind to 'valor' since it isn't a known property of 'p'.` |
| `(click)="sumarr()"` en el botón | `error TS2551: Property 'sumarr' does not exist on type 'PracticaComponent'. Did you mean 'sumar'?` |

Cómo se leen:

- Antes del mensaje viene el lugar exacto, por ejemplo `src/app/practica/practica.component.html:3:16`: el archivo, la línea y la columna.
- `TS` quiere decir que TypeScript revisó tu plantilla contra tu clase y encontró un nombre que no existe; cuando se parece a uno que sí existe, te sugiere el correcto. `NG` es un error de Angular: aquí, un atributo que ni el HTML ni Angular conocen.
- Mientras haya un error, el navegador se queda con la última versión que sí compiló (slide 60). "No cambia nada" casi siempre quiere decir "mira la terminal".

## Paso 9 · De tu arreglo a tu API

Hasta aquí todo vivía en la clase. El último paso le pide un dato a tu API con el servicio que ya tienes.

En `practica.component.ts`, arriba, junto al otro `import`:

```ts
import { Component, OnInit } from '@angular/core';

import { AvisosService } from '../servicios/avisos.service';
```

La clase ahora dice `implements OnInit`, y adentro agrega:

```ts
  total = 0;

  constructor(private avisosService: AvisosService) { }

  ngOnInit(): void {
    this.avisosService.listar().subscribe(avisos => this.total = avisos.length);
  }
```

En la plantilla, al final:

```html
  <p>Tu API tiene {{ total }} avisos publicados.</p>
```

**Qué debe verse:** `Tu API tiene 7 avisos publicados.`, con el número de tu base (como máximo 10: tu API los entrega de 10 en 10).

**Rómpelo, y regrésalo:** deja solo `this.avisosService.listar();`, sin el `.subscribe(...)`. El número se queda en 0, y en la pestaña Red sale una petición menos a `/api/avisos`: la de tu lista sigue, la de la práctica nunca salió. Sin `subscribe`, un Observable no hace nada (slide 34).

---

## Así queda

`practica.component.ts`:

```ts
import { Component, OnInit } from '@angular/core';

import { AvisosService } from '../servicios/avisos.service';

@Component({
  selector: 'app-practica',
  templateUrl: './practica.component.html',
  styleUrls: ['./practica.component.css']
})
export class PracticaComponent implements OnInit {
  nombre = 'Guardia nocturna';
  turnos = 3;
  pendientes = ['Revisar radios', 'Entregar reporte', 'Cambiar llantas'];
  nuevo = '';
  hoy = new Date();
  total = 0;

  constructor(private avisosService: AvisosService) { }

  ngOnInit(): void {
    this.avisosService.listar().subscribe(avisos => this.total = avisos.length);
  }

  sumar(): void {
    this.turnos++;
  }

  agregar(): void {
    this.pendientes.push(this.nuevo.trim());
    this.nuevo = '';
  }
}
```

`practica.component.html`:

```html
<section class="tarjeta">
  <h3>Hola, {{ nombre }}</h3>
  <p>Te tocan {{ turnos }} turnos: {{ turnos * 8 }} horas.</p>

  <button (click)="sumar()">Un turno más</button>
  <p *ngIf="turnos > 5" class="nota mal">Son demasiados turnos.</p>

  <ul>
    <li *ngFor="let pendiente of pendientes; let i = index">{{ i + 1 }}. {{ pendiente }}</li>
  </ul>

  <input [(ngModel)]="nuevo" placeholder="Nuevo pendiente">
  <button (click)="agregar()" [disabled]="!nuevo.trim()">Agregar</button>

  <p>{{ nombre | uppercase }} · {{ hoy | date:'dd/MM/yyyy' }}</p>

  <p>Tu API tiene {{ total }} avisos publicados.</p>
</section>
```

## Checkpoint

- La tarjeta de práctica muestra todo lo de arriba, y los dos botones responden.
- La terminal de `npm start` dice `Compiled successfully`.
- En tu PR va la carpeta `src/app/practica/`. Cuando termines, puedes quitar `<app-practica></app-practica>` de `app.component.html`: el componente se queda en el proyecto sin estorbar.

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| No aparece nada nuevo arriba de la página | Falta `<app-practica></app-practica>` en `app.component.html` |
| `error NG8001: 'app-practica' is not a known element` | El componente no está en `declarations` de `app.module.ts`. La CLI lo agrega sola; si creaste los archivos a mano, agrégalo tú |
| `error NG8002: Can't bind to 'ngModel' since it isn't a known property of 'input'.` | Falta `FormsModule` en los `imports` del módulo (ejercicio 3, paso 2) |
| El número de avisos se queda en 0 | Falta el `.subscribe(...)`, o `composer run dev` no está corriendo |
| Cambias algo y el navegador no cambia | Hay un error en la terminal de `npm start`: mientras exista, el navegador se queda con la última versión que compiló |
