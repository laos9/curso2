# Ejercicio 3 · Entrar con token: la sesión y el interceptor

Objetivo: entrar desde Angular con tu usuario del blog, guardar el token, y que cada petición a tu API lo lleve sin que ningún servicio tenga que acordarse.

Tiempo estimado: 25 minutos.

Parte del ejercicio 2: tu lista de avisos ya se ve en el 4200. La explicación del interceptor y de `BehaviorSubject` está en la lectura [`00-angular-por-dentro.md`](00-angular-por-dentro.md).

---

## Lo que ya sabes de la sesión 5

Tu API protege la escritura con `auth:sanctum`. Para escribir, quien llama necesita tres cosas:

1. Pedir un token con `POST /api/token`, mandando correo, contraseña y un nombre de dispositivo.
2. Guardarlo.
3. Mandarlo en cada petición protegida, en el encabezado `Authorization: Bearer <token>`.

Hoy lo hace tu aplicación de Angular. Las tres cosas quedan en dos piezas: un **servicio de sesión** (1 y 2) y un **interceptor** (3).

---

## Paso 1 · El servicio de sesión

En una terminal dentro de `frontend/`:

```bash
npx ng generate service servicios/sesion
```

Deja `src/app/servicios/sesion.service.ts` así:

```ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';

export interface Sesion {
  token: string;
  usuario: string;
  rol: string;
}

const CLAVE = 'sesion';

@Injectable({
  providedIn: 'root'
})
export class SesionService {

  // La sesion actual, o null si nadie ha entrado. Quien se suscribe a sesion$
  // se entera cada vez que cambia.
  private sesionSubject = new BehaviorSubject<Sesion | null>(this.leerGuardada());
  readonly sesion$ = this.sesionSubject.asObservable();

  constructor(private http: HttpClient) { }

  get token(): string | null {
    return this.sesionSubject.value?.token ?? null;
  }

  entrar(email: string, password: string): Observable<Sesion> {
    return this.http.post<Sesion>('/api/token', { email, password, dispositivo: 'angular' }).pipe(
      tap(sesion => {
        sessionStorage.setItem(CLAVE, JSON.stringify(sesion));
        this.sesionSubject.next(sesion);
      })
    );
  }

  yo(): Observable<{ id: number; nombre: string; rol: string }> {
    return this.http.get<{ id: number; nombre: string; rol: string }>('/api/yo');
  }

  salir(): void {
    sessionStorage.removeItem(CLAVE);
    this.sesionSubject.next(null);
  }

  private leerGuardada(): Sesion | null {
    const guardada = sessionStorage.getItem(CLAVE);
    return guardada ? JSON.parse(guardada) : null;
  }
}
```

- `Sesion` tiene las mismas llaves que responde tu `TokenController`: `token`, `usuario` y `rol`.
- `BehaviorSubject` es una variable que **avisa cuando cambia**. Guarda la sesión actual, y cualquier parte de la aplicación que se suscriba a `sesion$` se entera al entrar y al salir.
- `tap` hace algo con la respuesta sin cambiarla: guardar el token en cuanto llega.
- El token va a `sessionStorage` y no a `localStorage`: se borra solo al cerrar la pestaña. Es la elección de un sistema real en producción.

---

## Paso 2 · Formularios: FormsModule

Para leer lo que la persona escribe en un campo se usa `[(ngModel)]`, y eso vive en `FormsModule`. En `src/app/app.module.ts`, agrégalo:

```ts
import { FormsModule } from '@angular/forms';
```

y en `imports`:

```ts
  imports: [
    BrowserModule,
    HttpClientModule,
    FormsModule
  ],
```

---

## Paso 3 · El componente para entrar

```bash
npx ng generate component entrar
```

La clase, `src/app/entrar/entrar.component.ts`:

```ts
import { Component } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';

import { SesionService } from '../servicios/sesion.service';

@Component({
  selector: 'app-entrar',
  templateUrl: './entrar.component.html',
  styleUrls: ['./entrar.component.css']
})
export class EntrarComponent {
  email = 'editor@blog.test';
  password = '';
  error = '';
  enviando = false;
  quienSoy = '';
  quienSoyOk = false;

  constructor(public sesion: SesionService) { }

  entrar(): void {
    this.error = '';
    this.enviando = true;
    this.sesion.entrar(this.email, this.password).subscribe({
      next: () => {
        this.enviando = false;
        this.password = '';
      },
      error: (e: HttpErrorResponse) => {
        this.enviando = false;
        this.error = e.error?.message ?? `Tu API respondió ${e.status}`;
      }
    });
  }

  preguntarQuienSoy(): void {
    this.sesion.yo().subscribe({
      next: yo => {
        this.quienSoy = `200 · tu API te reconoce: ${yo.nombre} (${yo.rol})`;
        this.quienSoyOk = true;
      },
      error: (e: HttpErrorResponse) => {
        this.quienSoy = `${e.status} · tu API no sabe quién eres`;
        this.quienSoyOk = false;
      }
    });
  }

  salir(): void {
    this.sesion.salir();
    this.quienSoy = '';
  }
}
```

La plantilla, `src/app/entrar/entrar.component.html`:

```html
<section *ngIf="sesion.sesion$ | async as s; else formulario" class="panel">
  <div class="fila">
    <span>Entraste como <strong>{{ s.usuario }}</strong></span>
    <span class="chip">{{ s.rol }}</span>
    <button class="sec" (click)="preguntarQuienSoy()">¿Quién soy?</button>
    <button class="sec" (click)="salir()">Salir</button>
  </div>
  <p *ngIf="quienSoy" class="nota" [class.ok]="quienSoyOk" [class.mal]="!quienSoyOk" style="margin: 12px 0 0;">{{ quienSoy }}</p>
</section>

<ng-template #formulario>
  <form class="panel" (ngSubmit)="entrar()">
    <h2>Entrar</h2>
    <div class="fila">
      <input name="email" [(ngModel)]="email" placeholder="correo">
      <input name="password" type="password" [(ngModel)]="password" placeholder="contraseña">
      <button type="submit" [disabled]="enviando">Entrar</button>
    </div>
    <p *ngIf="error" class="error">{{ error }}</p>
  </form>
</ng-template>
```

Lo nuevo de esta plantilla:

| Escribes | Qué hace |
|---|---|
| `[(ngModel)]="email"` | Liga el campo con la propiedad, en los dos sentidos: lo que escribes llega a `email`, y si `email` cambia, el campo cambia |
| `(ngSubmit)="entrar()"` | Al enviar el formulario llama a tu método, sin recargar la página |
| `(click)="salir()"` | Lo mismo para un botón |
| `[disabled]="enviando"` | Liga un atributo del HTML a una propiedad |
| `sesion.sesion$ \| async as s` | Se suscribe a la sesión y la nombra `s` mientras exista |
| `else formulario` | Si no hay sesión, pinta el bloque `#formulario`. Es el `@else` de Blade |

---

## Paso 4 · Ponlo en la página

En `src/app/app.component.html`, arriba de la lista:

```html
<main class="contenido">
  <app-entrar></app-entrar>
  <app-avisos-lista></app-avisos-lista>
</main>
```

---

## Checkpoint A · Entras, pero tu API no te reconoce

1. Escribe una contraseña equivocada: aparece **Esas credenciales no coinciden con nuestros registros.** Es el mensaje que escribiste en tu `TokenController`, llegando por un 422.
2. Entra con `editor@blog.test` y `secreto123`: aparece **Entraste como Editor de guardia** con su rol.
3. Da clic en **¿Quién soy?**: aparece **401 · tu API no sabe quién eres**.

El 401 es correcto. El token está guardado (F12, pestaña Aplicación, Almacenamiento de sesión), pero ninguna petición lo lleva. Falta la tercera pieza.

---

## Paso 5 · El interceptor

```bash
npx ng generate interceptor interceptores/auth
```

Deja `src/app/interceptores/auth.interceptor.ts` así:

```ts
import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor
} from '@angular/common/http';
import { Observable } from 'rxjs';

import { SesionService } from '../servicios/sesion.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {

  constructor(private sesion: SesionService) {}

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    const token = this.sesion.token;

    if (token) {
      request = request.clone({
        setHeaders: { Authorization: `Bearer ${token}` }
      });
    }

    return next.handle(request);
  }
}
```

Es el middleware del lado del cliente. En la sesión 3 un middleware se paraba entre la petición y tu controlador; este se para entre tu código y la red. Cada petición que sale pasa por `intercept()`: si hay sesión, se le pega el token, y `next.handle(request)` la deja seguir.

Una petición de Angular no se modifica: se **clona** con el encabezado agregado. Por eso `request = request.clone(...)`.

`ng generate` no lo registra. En `src/app/app.module.ts`, cambia la importación de `@angular/common/http` y agrega el interceptor:

```ts
import { HTTP_INTERCEPTORS, HttpClientModule } from '@angular/common/http';
import { AuthInterceptor } from './interceptores/auth.interceptor';
```

y en `providers`:

```ts
  providers: [
    { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true }
  ],
```

`multi: true` dice que puede haber varios interceptores y este se suma a la lista. Un sistema real suele tener al menos dos: uno que pega el token y otro que atiende los errores.

---

## Checkpoint B

1. Da clic en **¿Quién soy?**: ahora dice **200 · tu API te reconoce: Editor de guardia (editor)**.
2. F12, pestaña **Red**, petición `yo`, encabezados de la solicitud: ahí va `Authorization: Bearer ...`.
3. Recarga la página: sigues dentro, porque la sesión se lee de `sessionStorage` al arrancar.
4. **Salir**, y vuelve a dar clic en entrar: todo regresa al formulario.

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| `error NG8002: Can't bind to 'ngModel' since it isn't a known property of 'input'` | Falta `FormsModule` en los `imports` de `app.module.ts` (paso 2) |
| Junto con el anterior, `error TS2322: Type 'Event' is not assignable to type 'string'` | Es el mismo problema: se va al agregar `FormsModule` |
| **¿Quién soy?** sigue en 401 después del paso 5 | El interceptor no está en `providers` de `app.module.ts`, o el encabezado dice otra cosa que `Bearer` |
| Con la contraseña correcta sale el mensaje de credenciales | Ese usuario no existe en tu base. En la raíz: `php artisan db:seed --class=UserSeeder` |
| `Too Many Attempts.` | El límite de intentos de tu ruta de token (sesión 5). Espera un minuto |
| Al dar clic en Entrar la página se recarga y no pasa nada | El formulario se envió como HTML normal: revisa que diga `(ngSubmit)` y que `FormsModule` esté en el módulo |
