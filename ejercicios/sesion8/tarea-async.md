# Tarea de la semana · Sesión 8

Tiempo estimado: 2.5 horas, sin contar los extras. Se entrega en el Pull Request de siempre.

En clase viste en vivo los bloques 3 y 4: entrar con token y que tu API te reconozca, y después crear, borrar y encontrarte con el 403 de tu Policy. **Esta tarea los consolida en tu propio proyecto**, y agrega lo que un equipo con frontend aparte hace todos los días: la pantalla pide algo, la API lo agrega, se prueba, y la pantalla lo usa.

Para repasar, en Moodle hay una actividad de unos 10 minutos: **¿Qué responde tu API?**, doce rondas en las que eliges qué código responde tu API en cada situación. Tu mejor intento se registra solo.

**Si alguna pieza se te fue en clase**, empieza por [`05-primeros-pasos.md`](../sesion7/05-primeros-pasos.md): nueve pasos cortos en un componente de práctica, sin token. **Si terminas todo y quieres más**, [`06-retos-avanzados.md`](../sesion7/06-retos-avanzados.md) trae cuatro retos: una pantalla por aviso con el router, editar con PUT, la paginación de Laravel y un interceptor que mide.

La explicación escrita de todo lo de clase está en la lectura [`00-angular-por-dentro.md`](../sesion7/00-angular-por-dentro.md).

---

## 1. Termina las guías de clase (40 min)

Lo que hicimos en vivo, ahora en tu proyecto y sin prisa, con las guías [`03-entrar-con-token.md`](../sesion7/03-entrar-con-token.md) y [`04-escribir-y-el-403.md`](../sesion7/04-escribir-y-el-403.md):

- [ ] el servicio de sesión con `BehaviorSubject`, y el formulario para entrar
- [ ] **¿Quién soy?** respondiendo **401** antes de registrar el interceptor
- [ ] el interceptor en `providers`, y **¿Quién soy?** respondiendo **200** con tu nombre y tu rol
- [ ] crear un aviso, con los errores del 422 debajo de cada campo
- [ ] borrar uno tuyo (**204**) y ver el **403** en uno sembrado por otro

Si la lista de avisos todavía no te carga de tu API, esa parte está en [`02-tu-primera-pantalla.md`](../sesion7/02-tu-primera-pantalla.md) y va primero.

**Anota las dos respuestas de ¿Quién soy?**: las vas a escribir en la descripción del PR.

---

## 2. Termina el ejercicio de TypeScript (35 min)

En clase hiciste los pasos 1 a 3 de [`01-typescript.md`](../sesion7/01-typescript.md): el JavaScript que no avisa, el mismo archivo como TypeScript, y la interfaz `Aviso` que encuentra los tres errores antes de correr. Siguen los pasos 4 a 7, en el mismo `practica-ts/avisos.ts`:

- [ ] **Paso 4**: el tipo `Rol` con los tres roles de tu Policy, y `puedeCrear(sesion: Sesion | null)`. Rómpelo con las tres variantes de la guía y anota qué dice el compilador.
- [ ] **Paso 5**: el genérico `Respuesta<T>`, el sobre `data` de Laravel para uno o para muchos.
- [ ] **Paso 6**: la clase `AvisosRepositorio`, que le pide los avisos a **tu API** desde Node con `fetch`. Y la prueba de `any` con `JSON.parse`.
- [ ] **Paso 7**: el decorador `@Pieza` en `practica-ts/decorador.ts`, para ver en qué consiste `@Component`.

Meta: `npx tsc -p practica-ts` sin errores, y los dos archivos corriendo con `node`.

---

## 3. Tu frontend necesita algo que tu API no tiene (20 min)

El formulario de aviso nuevo pide la categoría **por número**, porque tu API no tiene una ruta que liste las categorías. Agrégala y pruébala.

**En tu API.** En `routes/api.php`, junto a las rutas públicas:

```php
use App\Models\Categoria;

Route::get('/categorias', fn () => Categoria::orderBy('nombre')->get(['id', 'nombre']));
```

Devuelve solo `id` y `nombre`: lo que el formulario necesita y nada más.

**Su prueba.** Crea `tests/Feature/Api/CategoriasApiTest.php` (lo aprendiste en la sesión 6):

```php
<?php

use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('el listado de categorias responde 200 con id y nombre', function () {
    Categoria::factory()->count(3)->create();

    $this->getJson('/api/categorias')
        ->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJsonStructure([['id', 'nombre']]);
});
```

Comprueba: `php artisan test` en verde con la prueba nueva, y `/api/categorias` en el navegador te da la lista.

Este punto es el trabajo de todos los días cuando el frontend va aparte: **la pantalla necesitó algo, la API lo agregó, y la prueba lo deja fijo.**

---

## 4. El formulario de aviso nuevo, como en un sistema real (35 min)

En clase lo hiciste **por plantilla**, con `[(ngModel)]`. Un sistema real en producción usa casi siempre **formularios reactivos**: el formulario entero vive en la clase, con sus reglas. Aquí conviertes el tuyo, y de paso cambias el número de categoría por un `<select>` que lee tu ruta nueva.

**La interfaz y el servicio de categorías**, dentro de `frontend/`:

```bash
npx ng generate interface modelos/categoria
npx ng generate service servicios/categorias
```

```ts
// src/app/modelos/categoria.ts
export interface Categoria {
  id: number;
  nombre: string;
}
```

```ts
// src/app/servicios/categorias.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

import { Categoria } from '../modelos/categoria';

@Injectable({
  providedIn: 'root'
})
export class CategoriasService {

  constructor(private http: HttpClient) { }

  listar(): Observable<Categoria[]> {
    return this.http.get<Categoria[]>('/api/categorias');
  }
}
```

Aquí no hay `map`: esta ruta no pasa por un Resource, así que la lista llega sin el sobre `"data"`.

**El módulo.** En `app.module.ts`, importa también `ReactiveFormsModule` (el `FormsModule` se queda: lo usa el formulario de entrar):

```ts
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
```

```ts
  imports: [
    BrowserModule,
    HttpClientModule,
    FormsModule,
    ReactiveFormsModule
  ],
```

**La clase**, `aviso-nuevo.component.ts`, completa:

```ts
import { Component, EventEmitter, OnInit, Output } from '@angular/core';
import { NonNullableFormBuilder, Validators } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';

import { Aviso } from '../modelos/aviso';
import { Categoria } from '../modelos/categoria';
import { AvisosService } from '../servicios/avisos.service';
import { CategoriasService } from '../servicios/categorias.service';
import { SesionService } from '../servicios/sesion.service';

@Component({
  selector: 'app-aviso-nuevo',
  templateUrl: './aviso-nuevo.component.html',
  styleUrls: ['./aviso-nuevo.component.css']
})
export class AvisoNuevoComponent implements OnInit {
  @Output() creado = new EventEmitter<Aviso>();

  // Las mismas reglas que tu validate() de Laravel, del lado de la pantalla.
  form = this.fb.group({
    titulo: ['', [Validators.required, Validators.maxLength(120)]],
    contenido: ['', Validators.required],
    categoria_id: [null as number | null, Validators.required]
  });

  categorias: Categoria[] = [];
  errores: Record<string, string[]> = {};
  mensaje = '';
  enviando = false;

  constructor(
    private fb: NonNullableFormBuilder,
    private avisosService: AvisosService,
    private categoriasService: CategoriasService,
    public sesion: SesionService
  ) { }

  ngOnInit(): void {
    this.categoriasService.listar().subscribe(categorias => this.categorias = categorias);
  }

  guardar(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.errores = {};
    this.mensaje = '';
    this.enviando = true;
    this.avisosService.crear(this.form.getRawValue()).subscribe({
      next: creado => {
        this.enviando = false;
        this.mensaje = `201 · se creó "${creado.titulo}"`;
        this.form.reset();
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

| En la clase | Qué hace |
|---|---|
| `NonNullableFormBuilder` | Arma el formulario. "Non nullable": al limpiarlo, cada campo regresa a su valor inicial y no a `null` |
| `Validators.required`, `Validators.maxLength(120)` | Las reglas de tu `validate()` (`required`, `max:120`), ahora también en la pantalla |
| `form.invalid` y `markAllAsTouched()` | Si algo no cumple, no se manda nada y se encienden los mensajes de todos los campos |
| `form.getRawValue()` | Los valores del formulario, con la forma exacta de `NuevoAviso` |
| `form.reset()` | Limpia el formulario después del 201 |

**La plantilla**, `aviso-nuevo.component.html`, completa:

```html
<form *ngIf="sesion.sesion$ | async" class="panel" [formGroup]="form" (ngSubmit)="guardar()">
  <h2>Nuevo aviso</h2>

  <div class="campo">
    <label for="titulo">Título</label>
    <input id="titulo" formControlName="titulo">
    <span *ngIf="form.controls.titulo.touched && form.controls.titulo.hasError('required')" class="error">Escribe un título.</span>
    <span *ngIf="form.controls.titulo.hasError('maxlength')" class="error">Máximo 120 caracteres.</span>
    <span *ngIf="errores['titulo']" class="error">{{ errores['titulo'][0] }}</span>
  </div>

  <div class="campo">
    <label for="contenido">Contenido</label>
    <textarea id="contenido" formControlName="contenido"></textarea>
    <span *ngIf="form.controls.contenido.touched && form.controls.contenido.hasError('required')" class="error">Escribe el contenido.</span>
    <span *ngIf="errores['contenido']" class="error">{{ errores['contenido'][0] }}</span>
  </div>

  <div class="campo">
    <label for="categoria">Categoría</label>
    <select id="categoria" formControlName="categoria_id">
      <option [ngValue]="null">Elige una categoría</option>
      <option *ngFor="let c of categorias" [ngValue]="c.id">{{ c.nombre }}</option>
    </select>
    <span *ngIf="form.controls.categoria_id.touched && form.controls.categoria_id.hasError('required')" class="error">Elige una categoría.</span>
    <span *ngIf="errores['categoria_id']" class="error">{{ errores['categoria_id'][0] }}</span>
  </div>

  <button type="submit" [disabled]="enviando">Crear aviso</button>
  <p *ngIf="mensaje" class="nota" style="margin: 12px 0 0;">{{ mensaje }}</p>
</form>
```

`[formGroup]` liga el formulario de la clase y `formControlName` liga cada campo. Ya no hay `[(ngModel)]` ni `name`. `[ngValue]` guarda el número de la categoría, no el texto.

**Comprueba:**

1. Da clic en **Crear aviso** con todo vacío: salen **Escribe un título.**, **Escribe el contenido.** y **Elige una categoría.**, y en la pestaña Red **no sale ninguna petición**. Lo detuvo la pantalla.
2. Llénalo, elige una categoría y créalo: **201**, el aviso aparece en la lista y el formulario queda limpio.
3. Quita `Validators.maxLength(120)` y crea un aviso con un título de más de 120 caracteres: ahora sí sale la petición, y tu API responde **422** con `The titulo field must not be greater than 120 characters.` debajo del título. Regresa la regla.

El punto 3 es el que importa: **las reglas de la pantalla son comodidad, las de tu API son las que protegen.** Si alguien llama a tu API sin tu pantalla, solo quedan las segundas. Es lo mismo que viste en clase con el 403: la pantalla no es la que decide.

---

## 5. Cuando tu API ya no te reconoce (25 min)

Hoy **Salir** solo olvida el token en el navegador. En el servidor sigue vivo. Y si tu API deja de reconocer el token (porque se revocó), la pantalla sigue diciendo "Entraste como...". Dos arreglos.

**Salir de verdad.** En `sesion.service.ts`, separa olvidar de salir:

```ts
  // Revoca el token en tu API y despues lo olvida aqui.
  salir(): void {
    this.http.post('/api/token/revocar', {}).subscribe({
      next: () => this.olvidar(),
      error: () => this.olvidar()
    });
  }

  // Solo lo olvida en este navegador.
  olvidar(): void {
    sessionStorage.removeItem(CLAVE);
    this.sesionSubject.next(null);
  }
```

El interceptor todavía tiene el token cuando sale la petición de revocar: por eso se revoca primero y se olvida después.

**Un interceptor de errores.** Un sistema real tiene al menos dos interceptores: el que pega el token y uno que atiende los errores. El tuyo hace una sola cosa: si tu API responde 401 y había sesión, la olvida.

```bash
npx ng generate interceptor interceptores/errores
```

```ts
import { Injectable } from '@angular/core';
import {
  HttpErrorResponse,
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest
} from '@angular/common/http';
import { Observable, catchError, throwError } from 'rxjs';

import { SesionService } from '../servicios/sesion.service';

@Injectable()
export class ErroresInterceptor implements HttpInterceptor {

  constructor(private sesion: SesionService) {}

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    return next.handle(request).pipe(
      catchError((error: HttpErrorResponse) => {
        if (error.status === 401 && this.sesion.token) {
          this.sesion.olvidar();
        }
        return throwError(() => error);
      })
    );
  }
}
```

`catchError` atrapa el error en el camino de regreso, y `throwError` lo deja seguir hasta el componente, que sigue mostrando su mensaje.

Regístralo en `app.module.ts`, **después** del de autenticación:

```ts
    { provide: HTTP_INTERCEPTORS, useClass: ErroresInterceptor, multi: true }
```

Ese orden es el del laboratorio de la cadena que viste en clase: de ida, en el orden de los `providers`; de regreso, al revés. Por eso el de errores ve la respuesta primero.

Comprueba: entra, y en la terminal de la raíz revoca todos los tokens:

```bash
php artisan tinker --execute="DB::table('personal_access_tokens')->delete();"
```

Da clic en **¿Quién soy?**: tu API responde 401 y el formulario para entrar reaparece solo.

---

## 6. Extras

Opcionales, cuentan como extra en el PR.

### A. La tarjeta como componente

En el slide del árbol de componentes viste `@Input` y `@Output`. Aquí los usas: cada tarjeta de la lista pasa a ser un componente propio, como tu `<x-tarjeta-post>` de Blade.

```bash
npx ng generate component tarjeta-aviso
```

```ts
// src/app/tarjeta-aviso/tarjeta-aviso.component.ts
import { Component, EventEmitter, Input, Output } from '@angular/core';

import { Aviso } from '../modelos/aviso';

@Component({
  selector: 'app-tarjeta-aviso',
  templateUrl: './tarjeta-aviso.component.html',
  styleUrls: ['./tarjeta-aviso.component.css']
})
export class TarjetaAvisoComponent {
  @Input({ required: true }) aviso!: Aviso;
  @Input() puedeBorrar = false;

  @Output() borrar = new EventEmitter<Aviso>();
}
```

```html
<!-- src/app/tarjeta-aviso/tarjeta-aviso.component.html -->
<article class="tarjeta">
  <span class="chip">{{ aviso.categoria?.nombre }}</span>
  <h3>{{ aviso.titulo }}</h3>
  <p>{{ aviso.contenido }}</p>
  <small>{{ aviso.autor }} · {{ aviso.creado | date:'dd/MM/yyyy' }}</small>
  <button *ngIf="puedeBorrar" class="peligro" (click)="borrar.emit(aviso)">Borrar</button>
</article>
```

Y en `avisos-lista.component.html`, el `<article>` completo se cambia por:

```html
<div class="tarjetas">
  <app-tarjeta-aviso *ngFor="let aviso of avisos"
    [aviso]="aviso"
    [puedeBorrar]="(sesion.sesion$ | async) !== null"
    (borrar)="borrar($event)">
  </app-tarjeta-aviso>
</div>
```

La tarjeta no borra nada: avisa con `borrar.emit(aviso)`, y la lista decide con el `borrar()` que ya tenía. `$event` es lo que emitió la tarjeta. `required: true` es de Angular 16: si quitas `[aviso]="aviso"`, no compila.

### B. Esconder Borrar en los avisos ajenos

Para que la pantalla sepa qué avisos son tuyos necesita dos datos que tu API todavía no manda: el `id` de quien entró y el del autor de cada aviso.

1. En tu `TokenController`, agrega `'id' => $usuario->id` a la respuesta; y en tu `PostResource`, `'autor_id' => $this->user_id`.
2. En Angular, agrega `id: number` a `Sesion` y `autor_id: number` a `Aviso`.
3. En la lista, un método que decida:

```ts
  puedeBorrar(aviso: Aviso, s: Sesion): boolean {
    return s.rol === 'admin' || s.id === aviso.autor_id;
  }
```

4. Y úsalo en la condición del botón (o en `[puedeBorrar]`, si hiciste el extra A).

Es la misma regla de tu `PostPolicy`, escrita otra vez del lado de la pantalla. Escribe en el PR, en una línea, **por qué esconder el botón no protege nada**. El 403 que viste en clase es la respuesta.

### C. Un buscador que no satura tu API

Unos 40 minutos. Es el ejemplo clásico de por qué Angular usa Observables: una cadena de tres operadores decide **cuándo** preguntarle a tu API y **qué respuesta** mostrar.

**En tu API.** El `index()` de tu `Api/PostController` acepta `?q=`:

```php
public function index(Request $request)
{
    $avisos = Post::publicados()
        ->with(['categoria', 'user'])
        ->when($request->query('q'), fn ($consulta, $texto) => $consulta->where('titulo', 'like', "%{$texto}%"))
        ->latest()
        ->paginate(10);

    return PostResource::collection($avisos);
}
```

`when()` agrega el `where` solo si llegó texto: sin `q`, la lista sale completa, como antes. El texto viaja como parámetro de la consulta, no pegado al SQL.

Y su prueba, `tests/Feature/Api/BuscadorApiTest.php`:

```php
<?php

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('el buscador filtra los avisos por titulo', function () {
    Post::factory()->create(['titulo' => 'Cambio de horario en barandilla']);
    Post::factory()->create(['titulo' => 'Curso de primeros auxilios']);

    $this->getJson('/api/avisos?q=horario')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.titulo', 'Cambio de horario en barandilla');
});

test('sin texto devuelve todos los publicados', function () {
    Post::factory()->count(2)->create();

    $this->getJson('/api/avisos')
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');
});
```

Comprueba: `php artisan test` en verde con las dos pruebas nuevas, y `/api/avisos?q=curso` en el navegador trae solo los avisos con "curso" en el título. En SQLite, `like` no distingue mayúsculas: "curso" encuentra "Curso de primeros auxilios".

**En Angular, el servicio.** `listar()` acepta un texto:

```ts
listar(texto = ''): Observable<Aviso[]> {
  const params: Record<string, string> = texto ? { q: texto } : {};
  return this.http.get<{ data: Aviso[] }>('/api/avisos', { params }).pipe(
    map(respuesta => respuesta.data)
  );
}
```

`params` arma el `?q=...` de la dirección. El tipo `Record<string, string>` va escrito a propósito: sin él, TypeScript deduce un tipo que no encaja con la versión de `get()` que devuelve JSON, y el error que aparece desconcierta (está en la tabla del final).

**En Angular, la lista.** Un `FormControl` suelto para el buscador, y la cadena en `ngOnInit()`:

```ts
import { FormControl } from '@angular/forms';
import { catchError, debounceTime, distinctUntilChanged, map, of, switchMap, tap } from 'rxjs';
```

```ts
buscar = new FormControl('', { nonNullable: true });

ngOnInit(): void {
  this.cargar();

  this.buscar.valueChanges.pipe(
    debounceTime(300),              // espera a que dejen de teclear
    map(texto => texto.trim()),
    distinctUntilChanged(),         // si el texto no cambió, no vuelve a preguntar
    tap(() => {
      this.cargando = true;
      this.error = '';
    }),
    switchMap(texto => this.avisosService.listar(texto).pipe(
      // Dentro del switchMap: si una busqueda falla, el buscador sigue vivo.
      catchError(() => {
        this.error = 'No pude buscar en tu API.';
        return of([]);
      })
    ))
  ).subscribe(avisos => {
    this.avisos = avisos;
    this.cargando = false;
  });
}
```

En `cargar()`, que también se usa al crear un aviso, pide con lo que haya en el buscador: `this.avisosService.listar(this.buscar.value.trim())`.

En la plantilla, arriba de las tarjetas:

```html
<input class="buscador" type="search" placeholder="Buscar por título" [formControl]="buscar">
```

`[formControl]` viene de `ReactiveFormsModule`, que ya importaste en la parte 4.

| Pieza | Qué hace aquí |
|---|---|
| `valueChanges` | cada tecla es un valor nuevo |
| `debounceTime(300)` | espera 300 ms sin teclas antes de dejar pasar el texto |
| `distinctUntilChanged()` | si el texto quedó igual que la última vez, no pregunta |
| `switchMap` | lanza la búsqueda y, si llega un texto nuevo antes de la respuesta, **cancela la anterior** |
| `catchError`, dentro del `switchMap` | una búsqueda que falla no apaga el buscador |

**Compruébalo en la pestaña Red** (F12):

1. Escribe una palabra completa, rápido: sale **una sola** petición, con la palabra entera en `?q=`, no una por letra.
2. Agrega un espacio al final: no sale ninguna. `trim` deja el mismo texto, y `distinctUntilChanged` lo descarta.
3. Para ver la cancelación tu API tiene que tardar. Pon por un momento `usleep(1500000);` como primera línea de `index()`, escribe dos letras, espera medio segundo y escribe una tercera: la primera petición aparece como **cancelada**, y a la pantalla solo llega la segunda (unos segundos después: tu servidor atiende una petición a la vez). Quita el `usleep` al terminar.

En el PR, si hiciste este extra: cuántas peticiones salieron al escribir una palabra completa, y qué le pasó a la primera petición en el punto 3.

### D. La parte C del ejercicio de TypeScript

Unos 45 minutos. Los pasos 8 a 10 de [`01-typescript.md`](../sesion7/01-typescript.md), en `practica-ts/frontera.ts`: revisar lo que llega de tu API con `unknown` y un guardián de tipo, el estado de una petición como unión discriminada (con la forma exacta del 422 de Laravel) y los tipos de lo que mandas, derivados de `Aviso` con `Pick` y `Partial`.

---

## Cómo se entrega

```bash
git add -A
git commit -m "sesion 8: entrar, escribir y el 403"
git push origin HEAD
```

Revisa que `frontend/node_modules` y `frontend/practica-ts/salida` **no** aparezcan en los cambios: sus `.gitignore` los dejan fuera.

Y en Moodle, la URL de tu Pull Request en **Entrega Sesión 8**. Es el mismo PR de siempre: cada push se agrega solo.

En la descripción del PR van cuatro cosas:

1. Qué respondió **¿Quién soy?** antes de registrar el interceptor y qué respondió después.
2. Qué respondió tu API al borrar un aviso sembrado, y **quién tomó esa decisión**.
3. Qué pasó en el punto 3 de la parte 4: el título largo con la regla de la pantalla y sin ella.
4. Cuántas pruebas te reporta `php artisan test` con la de categorías.

---

## Checklist de la entrega

- [ ] `frontend/src/app/servicios/sesion.service.ts` y el componente `entrar`
- [ ] el interceptor de autenticación registrado en `providers`
- [ ] el componente `aviso-nuevo`, con su `@Output` y el `borrar()` de la lista
- [ ] `frontend/practica-ts/avisos.ts` y `decorador.ts`, compilando sin errores
- [ ] `HttpClientModule`, `FormsModule`, `ReactiveFormsModule` y los dos interceptores en `app.module.ts`
- [ ] `GET /api/categorias` en `routes/api.php` y su prueba en `tests/Feature/Api/`
- [ ] el formulario de aviso nuevo, reactivo y con el `<select>` de categorías
- [ ] `salir()` que revoca en el servidor, y el interceptor de errores
- [ ] `php artisan test` en verde
- [ ] la descripción del PR con los cuatro puntos de arriba

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| `NG8002: Can't bind to 'ngModel' since it isn't a known property of 'input'` | Falta `FormsModule` en los `imports` del módulo |
| Después de entrar, **¿Quién soy?** sigue en **401** | El interceptor no está en `providers`, o el encabezado no dice `Bearer` |
| `Too Many Attempts.` al entrar | Es el límite de la ruta de token: espera un minuto y vuelve a intentar |
| Al entrar sale `Esas credenciales no coinciden con nuestros registros.` | La contraseña es `secreto123`, o falta sembrar: `php artisan db:seed --class=UserSeeder` |
| `TS2341: Property 'sesion' is private and only accessible within class` | En la plantilla solo se ven las propiedades públicas. Cambia `private sesion` por `public sesion` en el constructor |
| La lista no se actualiza después del 201 | Falta `(creado)="lista.cargar()"` en `app.component.html`, o el `#lista` |
| `TypeError: fetch failed` en el paso 6 de TypeScript | Tu Laravel no está corriendo: `composer run dev` en la terminal 1 |
| `GET /api/categorias` responde 404 | La ruta no está en `routes/api.php`. Compruébalo con `php artisan route:list --path=api` |
| `GET /api/categorias` responde 500, y en el log `Class "Categoria" not found` | Falta el `use App\Models\Categoria;` arriba de `routes/api.php` |
| La prueba falla con `Call to undefined method ...::factory()` | Falta `use HasFactory;` en `Categoria` (sesión 6) |
| `Can't bind to 'formGroup' since it isn't a known property of 'form'` | Falta `ReactiveFormsModule` en los `imports` del módulo |
| El `<select>` sale vacío | Falta el `subscribe` en `ngOnInit`: sin él la petición no sale |
| Al elegir una categoría llega el texto y no el número | Usaste `[value]` en vez de `[ngValue]` |
| Los mensajes de los campos salen desde que abres la página | Falta el `touched &&` en la condición: el campo todavía no se ha tocado |
| Después de borrar los tokens, **¿Quién soy?** sigue en 200 | Cerraste y abriste sesión antes de probar, o el interceptor de errores no está en `providers` |
| En los cambios de git aparecen miles de archivos de `frontend/node_modules` | Se borró `frontend/.gitignore`. Vuelve a traerlo de la base del curso |
| En el extra C, `error TS2339: Property 'data' does not exist on type 'ArrayBuffer'` en `avisos.service.ts` | A `params` le falta su tipo. Escríbelo: `const params: Record<string, string> = ...` |
| En el extra C, sale una petición por cada letra | `switchMap` quedó antes que `debounceTime` en el `pipe`: el orden importa |
| En el extra C, después de una búsqueda que falla el buscador ya no responde | El `catchError` quedó después del `switchMap`, no dentro: el primer error terminó la cadena |
