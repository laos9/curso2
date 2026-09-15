# Ejercicio 6 · Retos avanzados

Para quién es: si terminaste la tarea, o por lo menos sus partes 1 a 5, y quieres más. Cada reto dice **qué tiene que pasar**, qué piezas de Angular usa y trae pistas; no trae el código completo. Se hacen en orden, porque cada uno parte del anterior. Cuentan como extra en el PR.

Tiempo estimado: unas 3 horas los cuatro.

Punto de partida: tu proyecto con el formulario reactivo de la tarea, el servicio de categorías y, si lo hiciste, el buscador del extra C. Todo lo que aquí se pide se probó en un proyecto como el tuyo; los mensajes son los reales.

Si en algún momento te atoras con una pieza básica, [`05-primeros-pasos.md`](05-primeros-pasos.md) la practica sola.

---

## Reto 1 · Una pantalla por aviso: el router

Unos 45 minutos.

**Qué tiene que pasar:**

- El título de cada tarjeta es un enlace. Al hacer clic, la dirección cambia a `/avisos/11`, sin recargar la página, y arriba de la lista aparece el aviso completo.
- Clic en otro título: cambian la dirección **y** el aviso.
- `/avisos/99999` dice `Ese aviso no existe.`, y después de eso otro clic sigue funcionando.
- Un enlace "Cerrar" regresa a `/` y el aviso desaparece. El botón Atrás del navegador hace lo mismo.

**Piezas:** un módulo de rutas con `RouterModule`, `<router-outlet>`, `routerLink`, `ActivatedRoute`, y dos operadores que ya conoces: `switchMap` y `catchError`.

**Pistas:**

**1.** La lista se queda fija en la página; el router pinta, arriba de ella, lo que diga la dirección. Crea `src/app/app-routing.module.ts`:

```ts
const routes: Routes = [
  { path: '', pathMatch: 'full', children: [] },
  { path: 'avisos/:id', component: AvisoDetalleComponent },
  { path: '**', redirectTo: '' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
```

La primera ruta es la página sin aviso: no pinta nada. La última manda cualquier dirección desconocida a esa. Agrega `AppRoutingModule` a los `imports` de `app.module.ts`, y `<router-outlet></router-outlet>` en `app.component.html`, entre `<app-entrar>` y el formulario de aviso nuevo.

**2.** `npx ng generate component aviso-detalle`, y en tu servicio un método `uno(id)` que pida `GET /api/avisos/{id}` y saque el aviso del sobre `data`. Si el aviso no existe, tu API responde 404.

**3.** En el detalle, el id sale de la dirección. **No lo leas una sola vez**: usa `this.ruta.paramMap`, que es un Observable, y encadena `map` para sacar el número y `switchMap` para pedir el aviso. Cuando pasas de `/avisos/11` a `/avisos/12`, Angular reusa el mismo componente: `ngOnInit` no vuelve a correr, pero `paramMap` entrega el id nuevo (se comprobó: es el mismo componente, con el título nuevo).

**4.** El `catchError` va **dentro** del `switchMap`, igual que en el buscador: afuera, el primer 404 apagaría la pantalla para siempre.

**5.** En la tarjeta, el título pasa a ser `<a [routerLink]="['/avisos', aviso.id]">{{ aviso.titulo }}</a>`.

**Rómpelo, y regrésalo:** lee el id con `this.ruta.snapshot.paramMap.get('id')` dentro de `ngOnInit`, en lugar de `paramMap`. El primer aviso carga bien; al hacer clic en otro título, la dirección cambia y el aviso de arriba **se queda igual**. `snapshot` es la foto de la dirección en el momento en que nació el componente.

---

## Reto 2 · Editar un aviso: PUT, un guard y una lista que se entera

Unos 60 minutos.

**Qué tiene que pasar:**

- Con sesión, el detalle muestra "Editar este aviso", que lleva a `/avisos/19/editar` con el formulario lleno: título, contenido y categoría.
- Guardar un aviso **tuyo**: tu API responde 200, regresas al detalle con el título nuevo y **la lista de abajo también lo muestra**, sin recargar la página.
- Guardar uno **ajeno**: `403 · ese aviso no es tuyo. Lo decidió tu PostPolicy, no el guard.`
- Sin sesión, `/avisos/19/editar` te regresa a `/`.

**Piezas:** un guard funcional (`CanActivateFn`), `canActivate` en la ruta, `form.setValue`, `http.put`, y un `Subject` en el servicio.

**Pistas:**

**1.** El guard es el de la sección 24 de la lectura, con otra dirección de regreso. En `src/app/guardias/sesion.guard.ts`:

```ts
export const sesionGuard: CanActivateFn = () =>
  inject(SesionService).token ? true : inject(Router).parseUrl('/');
```

Y la ruta: `{ path: 'avisos/:id/editar', component: AvisoEditarComponent, canActivate: [sesionGuard] }`, antes de la de `'**'`.

**2.** El formulario es el reactivo de tu tarea. Para llenarlo con el aviso que ya existe: `this.form.setValue({ titulo: aviso.titulo, contenido: aviso.contenido, categoria_id: aviso.categoria?.id ?? null })`. Aquí el id sí se puede leer con `snapshot`: de editar siempre se sale a otra ruta.

**3.** En el servicio, `actualizar(id, aviso)` con `http.put`. Tu `update()` de Laravel pide los mismos tres campos que `store()`. Al guardar, `this.router.navigate(['/avisos', this.id])`.

**4.** La lista no es hija del formulario de editar: no se conocen. Los conecta un `Subject` en el servicio:

```ts
private cambiosSubject = new Subject<void>();
readonly cambios$ = this.cambiosSubject.asObservable();
```

`actualizar()` avisa con un `tap(() => this.cambiosSubject.next())`, y la lista, en su `ngOnInit`, hace `this.avisosService.cambios$.subscribe(() => this.cargar())`. Es la idea del `BehaviorSubject` de tu sesión, sin guardar un valor: solo avisa que algo cambió.

**Compruébalo también:** con un título de más de 120 caracteres tu API responde 422 (`The titulo field must not be greater than 120 characters.`), igual que en la tarea: tu `update()` valida lo mismo que `store()`. Los avisos sembrados son del admin; para ver el 200 entra como editor, crea uno y edita ese.

**Para el PR:** el guard ya no te deja entrar a editar sin sesión. ¿Por qué tu API sigue respondiendo 403 a un aviso ajeno, y por qué está bien que lo haga aunque exista el guard?

---

## Reto 3 · De diez en diez: la paginación de Laravel

Unos 40 minutos.

**Qué tiene que pasar:**

- Con más de diez avisos publicados, debajo de la lista: `Anterior`, `Página 1 de 2 · 15 avisos` y `Siguiente`.
- `Siguiente` muestra los que siguen (en la última página, los que queden: 5 de 15) y se desactiva ahí; `Anterior` se desactiva en la primera.
- Si hiciste el extra C: una búsqueda nueva vuelve a empezar en la página 1.

**Piezas:** el `meta` que agrega `paginate()`, el parámetro `page`, `[disabled]`.

**Pistas:**

**1.** Tu API ya pagina: tu `index()` termina en `paginate(10)`, y por eso el JSON trae `data`, `links` y `meta`. Ábrelo en el navegador, en `/api/avisos`: en `meta` están `current_page`, `last_page`, `per_page` y `total`.

**2.** Para probarlo necesitas más de diez avisos publicados. En `php artisan tinker`:

```php
$admin = App\Models\User::where('email', 'admin@blog.test')->first();
App\Models\Post::factory()->count(8)->create(['user_id' => $admin->id, 'categoria_id' => App\Models\Categoria::first()->id]);
```

**3.** Un método del servicio que devuelva más que la lista:

```ts
export interface Pagina {
  avisos: Aviso[];
  actual: number;
  ultima: number;
  total: number;
}
```

`pagina(texto, numero)` manda `page` (y `q`, si hay texto) y arma una `Pagina` con `data` y los tres números de `meta`. Recuerda escribir el tipo de `params`, como en el extra C.

**4.** En la lista guarda `pagina`, `ultima` y `total`, y los botones llaman a `cargar(pagina - 1)` y `cargar(pagina + 1)`, cada uno con su `[disabled]`.

---

## Reto 4 · Un interceptor que mide, y peticiones que no llevan token

Unos 30 minutos.

**Qué tiene que pasar:**

- En la consola del navegador, una línea por petición con lo que tardó, por ejemplo `GET /api/avisos?page=1 · 48 ms` o `POST /api/token · 222 ms`. También las que fallan: `GET /api/avisos/99999 · 57 ms`.
- Con sesión, las lecturas públicas (`GET /api/avisos`, `GET /api/avisos/11`) viajan **sin** `Authorization`; `GET /api/yo`, `POST`, `PUT` y `DELETE` lo siguen llevando.

**Piezas:** un segundo interceptor, `finalize`, `performance.now()`, `HttpContextToken` y `HttpContext`.

**Pistas:**

**1.** `npx ng generate interceptor interceptores/tiempo`. La CLI no lo registra (lo viste en la clase): agrégalo a `providers` **primero**. Las peticiones recorren los interceptores en ese orden y las respuestas al revés, así que el primero envuelve a todos los demás y a la red.

**2.** Toma `performance.now()` antes de `next.handle(request)` y escribe la línea en un `finalize`, que corre al terminar, con respuesta o con error.

**3.** Una marca que viaja con la petición, en `auth.interceptor.ts`:

```ts
export const SIN_TOKEN = new HttpContextToken<boolean>(() => false);
```

`() => false` dice que, si nadie la pone, la marca vale `false`: todas las peticiones llevan token, como hasta ahora. El servicio la pone en sus lecturas públicas con `{ params, context: new HttpContext().set(SIN_TOKEN, true) }`, y el interceptor pregunta antes de clonar: `if (token && !request.context.get(SIN_TOKEN))`. `CategoriasService` no la tiene; si quieres, agrégasela igual.

**4.** Cómo compruebas los encabezados: pestaña Red, clic en `GET /api/avisos`, sección de encabezados de la petición. No debe aparecer `Authorization`. En `GET /api/yo`, sí.

**Para el PR:** tu API no pide token para leer avisos. ¿Qué ganas con no mandarlo en esas peticiones?

---

## Cómo se entrega

En el mismo PR, un commit por reto:

```bash
git add -A
git commit -m "reto 1: una pantalla por aviso"
git push origin HEAD
```

Y en la descripción del PR, una línea por reto con lo que comprobaste, más las respuestas a las dos preguntas "para el PR".

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| `error NG8001: 'router-outlet' is not a known element` | Falta `AppRoutingModule` en los `imports` de `app.module.ts` |
| `error NG8002: Can't bind to 'routerLink' since it isn't a known property of 'a'.` | La misma causa: `routerLink` viene de `RouterModule`, que exporta tu módulo de rutas |
| Al hacer clic en otro título cambia la dirección, pero no el aviso | Leíste el id con `snapshot`. Usa `paramMap` con `switchMap` |
| Después de un 404 ya no carga ningún aviso | El `catchError` quedó fuera del `switchMap`, como en el extra C |
| Al guardar tu propio aviso sale el 403 | Estás editando uno del admin: los avisos sembrados son suyos. Crea uno con tu usuario y edita ese |
| Editas y la lista de abajo sigue con el título viejo | Falta el `tap` que llama a `next()` en `actualizar()`, o la lista no se suscribió a `cambios$` |
| `Call to undefined method App\Models\Post::factory()` en tinker | Falta `use HasFactory;` en tu modelo `Post` (sesión 6) |
| La consola no muestra los tiempos | El interceptor de tiempos no está en `providers`: la CLI no lo registra |
| `GET /api/avisos` sigue llevando `Authorization` | Falta el `context` en esa petición del servicio, o el interceptor no pregunta por `SIN_TOKEN` |
