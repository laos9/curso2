# Ejercicio 4 · Escribir desde Angular, y el 403

Objetivo: crear avisos desde tu aplicación de Angular, borrar los tuyos, y ver en pantalla lo que decide tu `PostPolicy` cuando el aviso no es tuyo.

Tiempo estimado: 20 minutos.

Parte del ejercicio 3: ya entras con token y **¿Quién soy?** responde 200.

---

## Paso 1 · Dos métodos más en el servicio

En `src/app/modelos/aviso.ts`, debajo de la interfaz `Aviso`, agrega lo que se manda para crear uno:

```ts
export interface NuevoAviso {
  titulo: string;
  contenido: string;
  categoria_id: number | null;
}
```

Son los mismos tres campos que valida el `store()` de tu API.

En `src/app/servicios/avisos.service.ts`, importa también `NuevoAviso`:

```ts
import { Aviso, NuevoAviso } from '../modelos/aviso';
```

y agrega dos métodos debajo de `listar()`:

```ts
  crear(aviso: NuevoAviso): Observable<Aviso> {
    return this.http.post<{ data: Aviso }>('/api/avisos', aviso).pipe(
      map(respuesta => respuesta.data)
    );
  }

  borrar(id: number): Observable<void> {
    return this.http.delete<void>(`/api/avisos/${id}`);
  }
```

Ninguno de los dos habla del token. Lo pone el interceptor.

---

## Paso 2 · El formulario de aviso nuevo

```bash
npx ng generate component aviso-nuevo
```

La clase, `src/app/aviso-nuevo/aviso-nuevo.component.ts`:

```ts
import { Component, EventEmitter, Output } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';

import { Aviso, NuevoAviso } from '../modelos/aviso';
import { AvisosService } from '../servicios/avisos.service';
import { SesionService } from '../servicios/sesion.service';

@Component({
  selector: 'app-aviso-nuevo',
  templateUrl: './aviso-nuevo.component.html',
  styleUrls: ['./aviso-nuevo.component.css']
})
export class AvisoNuevoComponent {
  @Output() creado = new EventEmitter<Aviso>();

  aviso: NuevoAviso = { titulo: '', contenido: '', categoria_id: 1 };
  errores: Record<string, string[]> = {};
  mensaje = '';
  enviando = false;

  constructor(private avisosService: AvisosService, public sesion: SesionService) { }

  guardar(): void {
    this.errores = {};
    this.mensaje = '';
    this.enviando = true;
    this.avisosService.crear(this.aviso).subscribe({
      next: creado => {
        this.enviando = false;
        this.mensaje = `201 · se creó "${creado.titulo}"`;
        this.aviso = { titulo: '', contenido: '', categoria_id: 1 };
        this.creado.emit(creado);
      },
      error: (e: HttpErrorResponse) => {
        this.enviando = false;
        if (e.status === 422) {
          this.errores = e.error.errors;
        } else {
          this.mensaje = `${e.status} · tu API no lo creó`;
        }
      }
    });
  }
}
```

- `@Output() creado` es un aviso hacia afuera: cuando tu API responde 201, el componente lo **emite**, y quien lo esté escuchando se entera. Es el `dispatch` de los eventos de Livewire de la sesión 4.
- Con un 422, `e.error.errors` trae los errores de tu `validate()`, campo por campo, con la misma forma que en la sesión 5.

La plantilla, `src/app/aviso-nuevo/aviso-nuevo.component.html`:

```html
<form *ngIf="sesion.sesion$ | async" class="panel" (ngSubmit)="guardar()">
  <h2>Nuevo aviso</h2>

  <div class="campo">
    <label for="titulo">Título</label>
    <input id="titulo" name="titulo" [(ngModel)]="aviso.titulo">
    <span *ngIf="errores['titulo']" class="error">{{ errores['titulo'][0] }}</span>
  </div>

  <div class="campo">
    <label for="contenido">Contenido</label>
    <textarea id="contenido" name="contenido" [(ngModel)]="aviso.contenido"></textarea>
    <span *ngIf="errores['contenido']" class="error">{{ errores['contenido'][0] }}</span>
  </div>

  <div class="campo">
    <label for="categoria">Categoría (su número)</label>
    <input id="categoria" name="categoria_id" type="number" [(ngModel)]="aviso.categoria_id">
    <span *ngIf="errores['categoria_id']" class="error">{{ errores['categoria_id'][0] }}</span>
  </div>

  <button type="submit" [disabled]="enviando">Crear aviso</button>
  <p *ngIf="mensaje" class="nota" style="margin: 12px 0 0;">{{ mensaje }}</p>
</form>
```

El formulario solo aparece con sesión. Es el `@auth` de Blade.

La categoría va como número porque tu API todavía no tiene una ruta que liste las categorías. Agregarla es parte de la tarea.

---

## Paso 3 · Que la lista se recargue sola

En `src/app/app.component.html`:

```html
<main class="contenido">
  <app-entrar></app-entrar>
  <app-aviso-nuevo (creado)="lista.cargar()"></app-aviso-nuevo>
  <app-avisos-lista #lista></app-avisos-lista>
</main>
```

- `#lista` le pone nombre al componente de la lista dentro de esta plantilla.
- `(creado)="lista.cargar()"`: cuando el formulario emite `creado`, se llama al `cargar()` de la lista, el mismo que usa al arrancar.

---

## Checkpoint A · Crear

1. Da clic en **Crear aviso** con el formulario vacío: debajo de Título y de Contenido aparecen los errores de **tu** validación. Llegaron en un 422.
2. Llénalo y créalo: aparece **201 · se creó "..."** y el aviso sale **primero en la lista**, sin recargar la página.
3. Pon la categoría `99` y créalo: el error sale debajo de Categoría. Tu regla `exists:categorias,id` de la sesión 5.

---

## Paso 4 · Borrar, y el 403

En `src/app/avisos-lista/avisos-lista.component.ts`:

1. Importa lo que falta, arriba:

```ts
import { HttpErrorResponse } from '@angular/common/http';
import { SesionService } from '../servicios/sesion.service';
```

2. Agrega dos propiedades debajo de `error`:

```ts
  mensaje = '';
  mensajeOk = false;
```

3. Pide también la sesión en el constructor, como **pública** (la plantilla la va a leer):

```ts
  constructor(private avisosService: AvisosService, public sesion: SesionService) { }
```

4. Y el método, debajo de `cargar()`:

```ts
  borrar(aviso: Aviso): void {
    this.mensaje = '';
    this.avisosService.borrar(aviso.id).subscribe({
      next: () => {
        this.avisos = this.avisos.filter(a => a.id !== aviso.id);
        this.mensaje = `204 · borraste "${aviso.titulo}"`;
        this.mensajeOk = true;
      },
      error: (e: HttpErrorResponse) => {
        this.mensaje = e.status === 403
          ? '403 · ese aviso no es tuyo. Lo decidió tu PostPolicy, no Angular.'
          : `${e.status} · tu API no lo borró`;
        this.mensajeOk = false;
      }
    });
  }
```

En la plantilla, `avisos-lista.component.html`, agrega el mensaje debajo del de error:

```html
<p *ngIf="mensaje" class="nota" [class.ok]="mensajeOk" [class.mal]="!mensajeOk">{{ mensaje }}</p>
```

y el botón al final de cada tarjeta, debajo del `<small>`:

```html
    <button *ngIf="sesion.sesion$ | async" class="peligro" (click)="borrar(aviso)">Borrar</button>
```

---

## Checkpoint B · El 403, en pantalla

Con la sesión de `editor@blog.test`:

1. Borra el aviso que creaste en el checkpoint A: **204 · borraste "..."** y desaparece de la lista.
2. Borra uno de los avisos que ya estaban (son del admin): **403 · ese aviso no es tuyo. Lo decidió tu PostPolicy, no Angular.** El aviso se queda.
3. Sal, entra con `admin@blog.test` y `secreto123`, y borra cualquiera: el admin puede, por el `before()` de tu Policy.

---

## Lo que acabas de ver

El botón **Borrar** aparece en todas las tarjetas, y aun así el aviso ajeno no se borra. La decisión no está en Angular: está en tu API, en el `Gate::authorize` que llama a la Policy que escribiste en la sesión 3. La misma regla que gobierna tu blog, tu panel de Filament y tu API ahora también se ve en tu frontend, sin que la hayas escrito otra vez.

Esconder el botón en los avisos que no son tuyos es parte de la tarea. Esconderlo es comodidad para quien usa la pantalla; lo que protege es el 403.

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| `401 · tu API no lo creó` | La petición salió sin token: revisa el registro del interceptor (ejercicio 3, paso 5) |
| `403 · tu API no lo creó` al crear | Tu usuario no puede crear avisos. Tu Policy solo deja crear a `admin` y `editor`; los usuarios de práctica son `lector` |
| La lista no se actualiza después del 201 | Falta `(creado)="lista.cargar()"` o el `#lista` en `app.component.html` |
| `error TS2341: Property 'sesion' is private` | En el constructor de la lista escribiste `private sesion`. La plantilla solo lee lo público: cámbialo a `public sesion` |
| `error TS2339: Property 'borrar' does not exist on type 'AvisosListaComponent'` | Falta el método `borrar()` en la clase, o está fuera de las llaves de la clase |
| El mensaje de error sale en inglés (`The titulo field is required.`) | Es el idioma de tu Laravel. Lo que importa es que es **tu** validación la que habla |
