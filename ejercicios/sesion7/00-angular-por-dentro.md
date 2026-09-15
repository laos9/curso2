# Lectura · Angular por dentro

La versión escrita del bloque conceptual de la sesión 7. Todo lo que se explica en los slides, con el mismo código, para leerlo con calma y volver a él cuando abras un proyecto de Angular que no escribiste.

Tiempo de lectura: 50 minutos. Está en el orden de la clase: si tienes poco tiempo, las secciones 1 a 17 son las que usas en los ejercicios; de la 18 en adelante es lo que vas a encontrar en un sistema real.

---

## 1. Dos maneras de pintar la misma página

Tu blog de las sesiones 1 a 4 pinta sus páginas **en el servidor**. El navegador pide `/`, Laravel consulta la base, Blade arma el HTML completo y el navegador lo muestra. Cada clic en un enlace repite el ciclo entero: otra petición, otro HTML completo, otra página que se dibuja desde cero.

Una aplicación de Angular pinta **en el navegador**. La primera vez, el navegador baja una página casi vacía y un paquete de JavaScript. De ahí en adelante, lo que viaja por la red ya no es HTML: es **JSON**. Tu aplicación le pide los avisos a la API, recibe los datos y arma la pantalla ella misma.

| | Blade | Angular |
|---|---|---|
| Quién arma el HTML | el servidor, en PHP | el navegador, en JavaScript |
| Qué viaja en cada clic | una página completa | solo los datos, en JSON |
| Dónde vive la lógica de pantalla | en las vistas de Laravel | en la aplicación de Angular |
| Cuántos proyectos | uno | dos: la API y el frontend |

A una aplicación así se le llama **SPA** (*single page application*): hay una sola página HTML, y todo lo demás lo cambia JavaScript sin recargarla.

Medido con la aplicación de esta sesión: la lista de avisos pesa unos 3 KB de JSON, un aviso suelto 0.4 KB, y la aplicación compilada para producción son 64 KB transferidos, que se bajan una sola vez.

---

## 2. Cuándo conviene un frontend aparte

No es mejor ni peor que Blade. Resuelve otro problema.

Conviene cuando:

- **La misma API la consumen varios clientes**: la aplicación web, una app de teléfono, otro sistema. En la sesión 5 viste que tu API no sabe quién la llama.
- **La pantalla es muy interactiva**: formularios largos que cambian según lo que eliges, tablas que se filtran al escribir, varias cosas pasando a la vez.
- **Hay equipos separados**: quien hace la API y quien hace la pantalla trabajan en proyectos distintos, con su propio ritmo.

Lo que cuesta:

- **Dos proyectos, dos servidores, dos formas de desplegar.** Hoy lo vas a sentir: dos terminales.
- **Las reglas se escriben en la API**, no en la pantalla. Lo que la pantalla esconde, alguien lo puede pedir directo a la API. Por eso tu `PostPolicy` sigue siendo la que decide.
- **Más piezas que entender**: TypeScript, componentes, servicios, `Observable`. De eso trata esta lectura.

---

## 3. De dónde viene Angular

| Año | Qué pasó |
|---|---|
| 1995 | Nace JavaScript: el navegador puede ejecutar código |
| 2006 | jQuery: cambiar la página a mano, pieza por pieza, se vuelve fácil |
| 2010 | AngularJS, de Google: describes cómo se ve la pantalla según los datos, y el framework la mantiene al día |
| 2013 y 2014 | React y Vue: la misma idea, con otras formas |
| 2016 | Angular 2: Google lo reescribe desde cero, en TypeScript |
| 2023 | Angular 16, la versión de un sistema real en producción |
| 2026 | Angular 22, la actual |

El cambio de fondo entre jQuery y los frameworks es la dirección. Con jQuery escribías cada cambio: busca este elemento, cámbiale el texto, agrégale una fila. Con Angular escribes **cómo se ve la pantalla según los datos**, cambias los datos, y el framework se encarga del resto. Es la misma idea que ya usas en Blade (`@foreach ($avisos as $aviso)`), con una diferencia que se explica en la sección 14: aquí la pantalla sigue viva después de pintarse.

**AngularJS y Angular son dos frameworks distintos.** AngularJS es la versión 1.x, de 2010, y dejó de tener soporte en enero de 2022. Angular es todo lo de la 2 en adelante, reescrito desde cero. Si buscas ayuda y el resultado dice AngularJS, o usa `$scope` o `ng-repeat`, no te sirve.

Desde 2016 sale una versión mayor cada seis meses. Las fechas de la tabla son las de publicación de cada versión (la 16 salió en mayo de 2023 y la 22 en junio de 2026).

---

## 4. La versión 16, y lo que cambió después

Angular es un framework para construir aplicaciones que corren en el navegador. Lo mantiene Google, se escribe en **TypeScript** y trae todo lo necesario en una sola pieza: componentes, formularios, peticiones HTTP, rutas y una herramienta de línea de comandos, la **CLI**, que genera el código base. Es a la pantalla lo que Laravel es al servidor: un framework completo, con opinión sobre cómo se organizan las cosas.

En el curso se usa la **16**, la misma versión de un sistema real en producción, porque es la que vas a encontrar al darle mantenimiento. Las versiones posteriores cambiaron cosas que vas a ver en tutoriales de internet:

| En Angular 16 (lo que usas hoy) | En versiones más nuevas |
|---|---|
| `*ngIf="..."` y `*ngFor="..."` | `@if (...) { }` y `@for (...) { }`, desde la 17 |
| Componentes declarados en un `NgModule` | Componentes `standalone` por defecto, desde la 19 |
| `HttpClientModule` en el módulo | `provideHttpClient()` |
| La detección de cambios con zone.js | Signals, que en la 16 llegaron como vista previa |
| `ng serve` y `ng build` con webpack | esbuild, y Vite para `ng serve`, desde la 17 (sección 9) |

Si copias un ejemplo con `@if` y tu aplicación no compila, es de una versión más nueva. La idea es la misma; la sintaxis no.

Si corres `npx ng version` dentro de `frontend/`, vas a ver este aviso:

```
Warning: The current version of Node (22.23.2) is not supported by Angular.
```

Tu contenedor trae Node 22 y Angular 16 se publicó antes que él. **Compila y sirve igual**; se verificó en un Codespace. Al arrancar con `npm start` no aparece: el único aviso en amarillo es el de que es un servidor solo para desarrollo.

---

## 5. Cuánto dura una versión

Cada versión mayor de Angular recibe correcciones durante **18 meses**: seis meses de soporte activo y doce de soporte largo, en el que solo llegan arreglos de seguridad y de errores graves. La 16 salió en mayo de 2023, así que dejó de recibirlos a finales de 2024. Hoy tienen soporte la 20, la 21 y la 22.

Eso no significa que un sistema en la 16 deje de funcionar: funciona igual que el día que se escribió. Significa que si aparece un problema de seguridad en Angular 16, ya no va a llegar el parche. **Actualizar la versión es parte de mantener un sistema**, igual que en Laravel, y para actualizar primero hay que entender el sistema en la versión en que está. Por eso el curso usa la 16.

---

## 6. TypeScript en diez minutos, para quien sabe PHP

**Por qué existe.** JavaScript nació en 1995 para scripts pequeños, sin tipos: una variable guarda cualquier cosa y nadie revisa nada hasta que el código corre. Cuando las aplicaciones del navegador crecieron a cientos de miles de líneas, eso se volvió un problema: un campo mal escrito se descubre en el navegador de quien usa la pantalla, y un error silencioso (texto más número) no se descubre nunca. Microsoft respondió en 2012 con TypeScript, dirigido por Anders Hejlsberg (el autor de Turbo Pascal y arquitecto de C#): JavaScript con tipos, que se compila a JavaScript normal y se puede adoptar poco a poco.

**Por qué Angular lo usa.** En 2015 Google eligió TypeScript para reescribir Angular, y TypeScript agregó los decoradores que Angular necesitaba. Angular lo aprovecha en todo: los decoradores describen tus clases, el inyector sabe qué entregarte leyendo los tipos de tu constructor, y el compilador revisa tus plantillas contra tus clases.

La historia completa, con el código compilado que lo demuestra y un ejercicio de siete pasos, está en [`01-typescript.md`](01-typescript.md).

TypeScript es JavaScript con tipos. El navegador no lo entiende: la CLI lo traduce a JavaScript al compilar, y en esa traducción **los tipos se borran**.

```ts
// lo que escribes
interface Aviso {
  id: number;
  titulo: string;
  categoria?: { nombre: string };
}

function titular(aviso: Aviso): string {
  return aviso.titulo.toUpperCase();
}
```

```js
// lo que llega al navegador
function titular(aviso) {
  return aviso.titulo.toUpperCase();
}
```

La interfaz desaparece, igual que los tipos. El navegador nunca ve un tipo: existen para que el editor y el compilador te avisen **antes** de abrir la página.

**Los tipos que vas a ver hoy:**

| Tipo | Qué dice |
|---|---|
| `string`, `number`, `boolean` | texto, número, verdadero o falso |
| `Aviso[]` | un arreglo de avisos |
| `Sesion \| null` | una sesión, o nada |
| `categoria?` | el campo puede no venir |
| `interface Aviso { }` | la forma de un objeto |
| `Observable<Aviso[]>` | algo que va a traer un arreglo de avisos (un tipo con parámetro, un *genérico*) |

**Casi todo tiene un pariente en el PHP 8 que ya escribes.** Una clase con promoción de propiedades en el constructor:

```php
class AvisosService
{
    public function __construct(private HttpClient $http) {}
}
```

```ts
export class AvisosService {
  constructor(private http: HttpClient) { }
}
```

En los dos, `private` en el parámetro crea la propiedad y le asigna el valor. En PHP la usas como `$this->http`; en TypeScript, como `this.http`.

| TypeScript | PHP |
|---|---|
| `avisos: Aviso[] = [];` | `public array $avisos = [];` |
| `aviso.categoria?.nombre` | `$aviso->categoria?->nombre` |
| `` `Borraste ${aviso.titulo}` `` (con comillas invertidas) | `"Borraste {$aviso->titulo}"` |
| `avisos => { ... }` | `fn ($avisos) => ...` |
| `import { Aviso } from '../modelos/aviso';` | `use App\Models\Aviso;` |
| `export class` | una clase pública en su archivo |

Una interfaz es **una promesa de cómo viene un dato**, no una revisión. TypeScript no mira la respuesta real de tu API: si tu `PostResource` cambia un nombre de llave, TypeScript no se entera. Lo que sí hace es revisar que tu código use la interfaz como dice.

---

## 7. Los decoradores

Encima de casi toda clase de Angular hay una marca con `@`:

```ts
@Component({
  selector: 'app-avisos-lista',
  templateUrl: './avisos-lista.component.html'
})
export class AvisosListaComponent { }
```

Un **decorador** es una función que le pega información a una clase. La clase sigue siendo una clase normal; el decorador le dice a Angular qué es y cómo usarla. PHP 8 tiene la misma idea con los atributos, `#[...]`.

Los cinco que usas en esta sesión:

| Decorador | Dice que |
|---|---|
| `@Component` | la clase es un pedazo de pantalla, con su plantilla |
| `@Injectable` | la clase es un servicio que el inyector puede entregar |
| `@NgModule` | la clase es el registro de piezas de la aplicación |
| `@Input` | (en una propiedad) es un dato que llega del componente padre |
| `@Output` | (en una propiedad) es un evento que sale hacia el padre |

---

## 8. Lo que TypeScript atrapa antes del navegador

Cuatro errores reales, copiados de la terminal de `npm start` en un proyecto como el tuyo. Los cuatro aparecen **antes** de abrir el navegador, y mientras existan la página no recibe la versión nueva.

| Error | Qué lo provocó |
|---|---|
| `error TS2564: Property 'avisos' has no initializer and is not definitely assigned in the constructor.` | `avisos: Aviso[];` sin el `= []`. Cada propiedad necesita valor desde el principio |
| `error TS2339: Property 'title' does not exist on type 'Aviso'.` | `{{ aviso.title }}` en la plantilla. La plantilla también se revisa contra la interfaz |
| `error TS2532: Object is possibly 'undefined'.` | `{{ aviso.categoria.nombre }}` sin `?`. La interfaz dice que la categoría puede no venir |
| `error TS2341: Property 'sesion' is private and only accessible within class 'AvisosListaComponent'.` | `constructor(private sesion: ...)` y la plantilla leyendo `sesion`. La plantilla solo lee lo público |

En Blade, `$aviso->title` habría pintado vacío sin avisar, y te enterabas en el navegador. Aquí te enteras al guardar.

---

## 9. Del código al navegador

`npm start` corre `ng serve`, que hace cuatro cosas cada vez que guardas:

1. **El compilador de Angular** convierte cada plantilla en una función de JavaScript.
2. **TypeScript** se traduce a JavaScript y se borran los tipos.
3. **webpack** junta todo en unos pocos archivos: `main.js`, `polyfills.js`, `styles.css`.
4. El servidor de desarrollo **avisa al navegador** por un websocket, y la página se recarga sola.

El paso 1 es el que más sorprende. Tu plantilla nunca llega al navegador como HTML. Esto es lo que el compilador escribió para la lista de avisos de esta sesión (real, recortado):

```html
<article *ngFor="let aviso of avisos" class="tarjeta">
  <span class="chip">{{ aviso.categoria?.nombre }}</span>
  <h3>{{ aviso.titulo }}</h3>
```

```js
function AvisosListaComponent_article_4_Template(rf, ctx) {
  if (rf & 1) {                        // crear, una vez
    ɵɵelementStart(0, "article", 8)(1, "span", 9);
    ɵɵtext(2);
    ɵɵelementEnd();
    ɵɵelementStart(3, "h3");
    ɵɵtext(4);
    // ...
  }
  if (rf & 2) {                        // actualizar, cada vez
    const aviso_r5 = ctx.$implicit;
    ɵɵadvance(2);
    ɵɵtextInterpolate(aviso_r5.categoria == null ? null : aviso_r5.categoria.nombre);
    ɵɵadvance(2);
    ɵɵtextInterpolate(aviso_r5.titulo);
    // ...
  }
}
```

Cada plantilla se vuelve una función con dos partes: una que **crea** los elementos una vez y otra que **actualiza** los textos cuando cambian los datos. Por eso Angular puede cambiar un solo texto sin volver a dibujar la página. Fíjate también en `aviso.categoria?.nombre`: el `?.` se volvió una comparación con `null`.

`ng build` hace lo mismo para producción y deja el resultado en `dist/`, con nombres de archivo con huella (`main.817ce7c7d5928961.js`), como el `npm run build` de Vite en la sesión 1. En la aplicación de esta sesión, el paquete de desarrollo pesa 2.4 MB y el de producción 225 KB, que viajan comprimidos como 64 KB.

### Con qué corre: webpack, no Vite

En la sesión 1 viste que Laravel usa **Vite** para tu CSS y tu JavaScript. Angular 16 no usa Vite: su CLI trabaja con **webpack**, y quien atiende el 4200 cuando corres `npm start` (que es `ng serve`, lo dice tu `package.json`) es **webpack-dev-server**. Es la misma historia que contó la sesión 1, con otras fechas:

| | Antes | Después |
|---|---|---|
| Laravel | Laravel Mix, que por dentro es webpack (2017) | Vite (2022) |
| Angular | la CLI con webpack, hasta la 16: **tu proyecto** | esbuild y Vite, de fábrica desde la 17 (noviembre de 2023) |

En el día a día se nota así:

| | Tu Laravel, con Vite | Tu Angular 16, con webpack |
|---|---|---|
| Al desarrollar | `npm run dev` en el 5173: sirve cada archivo cuando el navegador lo pide | `npm start` en el 4200: empaqueta toda la aplicación antes de servirla, y al guardar vuelve a empaquetar lo que cambió |
| Al guardar | HMR: cambia la pieza sin recargar la página | recarga la página completa. `ng serve --hmr` existe, pero viene apagado |
| Para producción | `npm run build` deja todo en `public/build/` | `ng build` deja todo en `dist/` |

**Cuál usa un proyecto lo dice su `angular.json`**, en la línea `"builder"` de `build`:

| Lo que dice | Con qué compila |
|---|---|
| `@angular-devkit/build-angular:browser` | webpack. Es el de tu proyecto |
| `@angular-devkit/build-angular:application` | esbuild, y `ng serve` con Vite. Es el que pone `ng new` desde la 17 |
| `@angular/build:application` | lo mismo, con el nombre que usa `ng new` en la versión actual, la 22 |

**La 16 ya trae esbuild y Vite, en vista previa.** Se probó en un proyecto como el tuyo, cambiando el constructor a `browser-esbuild`: `ng build` sin caché tardó **3.5 s**, contra **9.6 s** con webpack, con casi el mismo peso de salida (68 KB transferidos en los dos); y `ng serve` quedó servido por Vite: la dirección `/@vite/client` respondió, cuando con webpack da 404. Los tiempos son del contenedor del curso; en tu Codespace serán otros. Al compilar, Angular avisa que ese constructor todavía no se recomienda para producción. Por eso tu proyecto se queda en webpack: cambiar el constructor de un sistema real es parte de actualizarlo de versión (sección 5), no un ajuste a mitad de la 16.

Dos cosas de esta sesión son de webpack-dev-server, no de Angular: el `allowedHosts` del `angular.json` del curso y el mensaje `Invalid Host header` (sección 23).

---

## 10. Cómo arranca una aplicación de Angular

```
index.html  →  main.ts  →  AppModule  →  AppComponent  →  su plantilla
```

1. `src/index.html` es la única página. Trae una etiqueta que el navegador no conoce: `<app-root></app-root>`.
2. `src/main.ts` arranca Angular con el módulo principal, `AppModule`.
3. `AppModule` dice qué piezas existen y cuál es la raíz: `bootstrap: [AppComponent]`.
4. `AppComponent` tiene el selector `app-root`. Angular lo encuentra en `index.html` y pinta ahí su plantilla.

| Laravel | Angular |
|---|---|
| `public/index.php` | `src/index.html` y `src/main.ts` |
| `bootstrap/app.php` y los providers | `app.module.ts` |
| la ruta y el controlador | el componente |
| la vista de Blade | la plantilla del componente |

---

## 11. El componente

Un componente es un pedazo de pantalla con su lógica. Tiene tres archivos:

```
avisos-lista/
├── avisos-lista.component.ts     la clase: datos y métodos
├── avisos-lista.component.html   la plantilla: cómo se ven
└── avisos-lista.component.css    sus estilos, que solo le aplican a él
```

```ts
@Component({
  selector: 'app-avisos-lista',                    // la etiqueta
  templateUrl: './avisos-lista.component.html',    // su HTML
  styleUrls: ['./avisos-lista.component.css']      // su CSS
})
export class AvisosListaComponent implements OnInit {
  avisos: Aviso[] = [];          // el estado que pinta la plantilla
  cargando = true;

  constructor(private avisosService: AvisosService) { }   // lo que necesita

  ngOnInit(): void {             // cuando aparece en pantalla
    this.cargar();
  }

  cargar(): void { ... }         // lo que la plantilla puede llamar
}
```

El `selector` es el nombre de la etiqueta. Donde escribas `<app-avisos-lista></app-avisos-lista>`, aparece el componente. Es la misma idea que tu `<x-tarjeta-post>` de Blade.

**El ciclo de vida.** Angular llama a ciertos métodos en momentos fijos:

| Momento | Para qué se usa |
|---|---|
| `constructor` | se crea el objeto: aquí solo se reciben dependencias |
| `ngOnInit()` | el componente ya está en pantalla: aquí se piden datos. Es el `mount()` de Livewire |
| `ngOnDestroy()` | el componente se va: aquí se limpia lo que quedó escuchando |

Los datos no se piden en el constructor, aunque funcione: el constructor es para recibir, `ngOnInit()` es para empezar a trabajar.

**Sus estilos no se salen de él.** Angular reescribe el CSS de cada componente. Si en `avisos-lista.component.css` escribes `h3 { color: #14305c; }`, el compilador lo convierte en `h3[_ngcontent-%COMP%] { color: #14305c; }` (verificado en el código compilado), y cada elemento de la plantilla del componente lleva un atributo como `_ngcontent-ng-c458428543`. Un `h3` de otro componente no tiene ese atributo y no se entera. Los estilos de `src/styles.css`, en cambio, son para toda la aplicación.

Los componentes se crean con la CLI, igual que `php artisan make:`:

```bash
npx ng generate component avisos-lista
```

Crea los tres archivos y **registra el componente en `app.module.ts` solo**.

---

## 12. La plantilla, y las cuatro formas de conectarla con la clase

La plantilla es HTML con marcas de Angular. Hay cuatro formas de conectarla con la clase:

| En la clase | En la plantilla | Se llama | Dirección |
|---|---|---|---|
| `titulo = 'Avisos'` | `{{ titulo }}` | interpolación: pinta un texto | de la clase a la pantalla |
| `enviando = true` | `[disabled]="enviando"` | propiedad: liga un atributo | de la clase a la pantalla |
| `salir(): void { }` | `(click)="salir()"` | evento: la pantalla llama a la clase | de la pantalla a la clase |
| `email = ''` | `[(ngModel)]="email"` | doble: las dos cosas | en los dos sentidos |

La regla de los signos: `[ ]` es un dato que **entra** a la pantalla, `( )` es un evento que **sale** de ella, y `[( )]` son las dos cosas. A `[( )]` se le dice "la banana en la caja".

En Blade solo existía la primera fila: `{{ }}` pinta una vez, en el servidor, y manda el resultado. Las otras tres existen porque aquí **la página sigue viva** después de pintarse: si `avisos` cambia, la lista se vuelve a pintar sola. Por eso al borrar un aviso basta con quitarlo del arreglo.

| Angular | Blade |
|---|---|
| `{{ aviso.titulo }}` | `{{ $aviso->titulo }}` |
| `*ngIf="cargando"` | `@if ($cargando)` |
| `*ngIf="x; else otro"` | `@else` |
| `*ngFor="let aviso of avisos"` | `@foreach ($avisos as $aviso)` |
| `[disabled]="enviando"` | `@disabled($enviando)` |
| `[class.ok]="mensajeOk"` | `@class(['ok' => $mensajeOk])` |
| `(click)="borrar(aviso)"` | `wire:click` de Livewire |
| `[(ngModel)]="email"` | `wire:model` de Livewire |

---

## 13. Directivas y pipes

Lo que va dentro de las marcas de la plantilla tiene nombre.

**Directivas estructurales**: deciden qué elementos existen. Llevan asterisco.

```html
<p *ngIf="cargando">Cargando avisos...</p>
<article *ngFor="let aviso of avisos; let i = index">
```

El asterisco es un atajo: Angular vuelve ese elemento una plantilla aparte, que pinta cero, una o muchas veces. En el código compilado de la sección 9 se ve: el botón con `*ngIf` de cada tarjeta quedó como su propia función, `AvisosListaComponent_article_4_button_10_Template`. En código real también vas a ver `trackBy` junto a `*ngFor`, para que Angular reconozca cada elemento de la lista entre una actualización y otra.

**Directivas de atributo**: cambian cómo se ve un elemento que ya existe.

```html
<p [class.ok]="mensajeOk">
<p [ngClass]="{ ok: mensajeOk, mal: !mensajeOk }">
```

**Pipes**: transforman un valor al pintarlo, sin cambiar el dato.

| En la plantilla | Qué pinta |
|---|---|
| `{{ aviso.creado \| date:'dd/MM/yyyy' }}` | la fecha como 10/09/2026 |
| `{{ aviso.titulo \| uppercase }}` | el título en mayúsculas |
| `{{ aviso \| json }}` | el objeto entero, para depurar |
| `{{ sesion$ \| async }}` | el valor actual de un `Observable`: se suscribe y se desuscribe solo |

La fecha llega de tu API como texto (JSON no tiene tipo fecha, sesión 5), y el pipe `date` la convierte al mostrarla.

---

## 14. La detección de cambios: cómo sabe Angular que tiene que volver a pintar

Cambias `this.avisos` en tu clase y la pantalla se actualiza. Nadie escribió "vuelve a dibujar". ¿Quién se entera?

En Angular 16 lo resuelve una biblioteca que viene con él, **zone.js**. zone.js envuelve todo lo asíncrono del navegador: los clics, las respuestas HTTP, los temporizadores. Cuando algo de eso termina, le avisa a Angular, y Angular hace una **detección de cambios**: recorre el árbol de componentes de arriba abajo, vuelve a evaluar cada enlace de cada plantilla (la parte "actualizar" del código compilado de la sección 9) y **toca solo lo que cambió**.

| Pasa esto | Lo que hace Angular |
|---|---|
| Clic en **¿Quién soy?** | revisa todo; nada cambió todavía, porque la petición apenas salió |
| Llega la respuesta de `/api/yo` | revisa todo; cambió `quienSoy` en el componente de entrar, y actualiza solo ese texto |
| Llega el token al entrar | revisa todo; cambian los tres componentes que leen la sesión |
| Pasa un temporizador | revisa todo, aunque ningún dato haya cambiado |

La última fila explica por qué Angular siguió evolucionando: revisar todo el árbol después de cualquier cosa asíncrona es revisar de más. Las versiones nuevas agregaron **signals**, valores que avisan exactamente qué cambió. En la 16 llegaron como vista previa, y un sistema real escrito en la 16 usa zone.js.

La diferencia con Livewire, que conociste en la sesión 4: en Livewire, cada interacción va al servidor, el servidor vuelve a pintar el componente y el navegador compara el HTML nuevo con el viejo. En Angular no hay viaje al servidor para pintar: el navegador vuelve a evaluar tus enlaces.

---

## 15. El módulo

`app.module.ts` es el registro de la aplicación:

```ts
@NgModule({
  declarations: [AppComponent, AvisosListaComponent],
  imports: [BrowserModule, HttpClientModule, FormsModule],
  providers: [
    { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true }
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
```

| Sección | Qué va |
|---|---|
| `declarations` | tus componentes. `ng generate component` los agrega solo |
| `imports` | módulos de Angular que usas: `HttpClientModule` para pedir datos, `FormsModule` para `ngModel` |
| `providers` | piezas extra para el inyector, como los interceptores |
| `bootstrap` | el componente raíz |

Los errores más comunes del primer día salen de aquí: usar `HttpClient` sin `HttpClientModule`, o `ngModel` sin `FormsModule`.

---

## 16. Servicios e inyección de dependencias

Un servicio es una clase que no pinta nada: hace trabajo para los componentes. El más común es el que habla con la API.

```ts
@Injectable({
  providedIn: 'root'
})
export class AvisosService {
  constructor(private http: HttpClient) { }
}
```

- `@Injectable` dice que Angular puede crearlo y entregarlo.
- `providedIn: 'root'` dice que hay **una sola instancia** para toda la aplicación.
- El componente lo pide en su constructor, y Angular se lo entrega.

Quien entrega es el **inyector**. Cuando la lista pide un `AvisosService`, el inyector revisa si ya tiene uno. No lo tiene: lo crea, y como el servicio a su vez pide `HttpClient`, también lo crea. Cuando el formulario de aviso nuevo pide el mismo servicio, recibe **la misma instancia**. Nunca escribes `new AvisosService(...)`.

Es el contenedor de servicios de Laravel: lee el tipo del parámetro del constructor y entrega el objeto. `providedIn: 'root'` es el equivalente de registrar un `singleton`.

Hay una segunda forma de pedir una dependencia, sin constructor, que vas a ver en código real:

```ts
private router = inject(Router);
```

Hace lo mismo. Se usa sobre todo en funciones que no son clases, como los guards de rutas (sección 24).

Cuando el inyector no sabe fabricar lo que le pides, lo dice: `NullInjectorError: No provider for HttpClient!` significa "me pediste un `HttpClient` y nadie me dijo cómo hacerlo". La cura es registrar lo que falta, en ese caso `HttpClientModule`.

La regla de organización es la misma que separar el controlador del modelo: **los componentes no hablan con `HttpClient`**. Le piden datos a un servicio, y el servicio es el único que sabe las direcciones de la API. En un sistema real hay un servicio por recurso de la API, en una carpeta propia.

---

## 17. HttpClient y el Observable

En PHP, una petición HTTP detiene todo hasta que llega la respuesta:

```php
$avisos = Http::get('.../api/avisos')->json('data');
// aqui ya tienes los avisos
```

En el navegador eso congelaría la pantalla. Por eso `HttpClient` no devuelve los avisos: devuelve un **`Observable`**, una respuesta que va a llegar después.

```ts
this.avisosService.listar().subscribe({
  next: avisos => { this.avisos = avisos; },
  error: () => { this.error = 'No pude hablar con tu API.'; }
});
```

`subscribe` dice qué hacer cuando llegue (`next`) y qué hacer si falla (`error`). Mientras tanto la pantalla sigue funcionando.

Tres cosas que conviene saber desde el principio:

- **Sin `subscribe`, la petición no sale.** El `Observable` es una receta, no una petición en curso. Si llamas a `listar()` y no te suscribes, tu API nunca se entera, y en la pestaña Red no aparece nada.
- **`pipe` transforma la respuesta antes de entregarla.** Tu `PostResource` envuelve la lista en `"data"`, y `map(respuesta => respuesta.data)` la saca del sobre.
- **Los errores de la API llegan a `error`** como un `HttpErrorResponse`: `e.status` es el código (401, 403, 422) y `e.error` es el cuerpo JSON que mandó Laravel. Por eso el mensaje de tu `TokenController` y los errores de tu `validate()` aparecen tal cual en Angular.

`get<{ data: Aviso[] }>` le dice a TypeScript qué forma tiene la respuesta. Como toda interfaz, es una promesa: no se revisa. La guía de HttpClient de Angular 16 lo dice igual: es una revisión al compilar, no una garantía de lo que responda el servidor. El paso 8 de `01-typescript.md` muestra cómo revisarlo de verdad, con `unknown` y un guardián de tipo.

`HttpClient` además manda por su cuenta el encabezado `Accept: application/json, text/plain, */*`. En la sesión 5 lo escribías a mano en el `curl`, y sin él Laravel respondía con una redirección al login en vez de un 401. Desde Angular ese caso no se da.

---

## 18. RxJS: operadores y Subjects

`Observable`, `map`, `tap` y `BehaviorSubject` vienen de **RxJS**, una biblioteca que Angular usa para todo lo asíncrono. La imagen que ayuda es un tubo: por él pasan valores, cada operador que pones en `pipe()` los transforma, y al final alguien se suscribe.

**Si usas colecciones de Laravel, ya conoces la idea.** `collect($avisos)->filter(...)->map(...)` recorre una lista que ya tienes completa. `pipe(filter(...), map(...))` hace lo mismo con valores que **van llegando con el tiempo**: la respuesta de tu API, cada tecla que se escribe en un buscador, cada cambio de la sesión. Por eso varios operadores se llaman igual que los métodos de una colección.

**Un valor que recorre el tubo.** HttpClient emite la respuesta completa, `map` le quita el sobre, `tap` la mira sin cambiarla (sirve para guardar algo o para depurar) y `next` la recibe limpia. Después el `Observable` termina: el de HttpClient emite **una sola vez**.

**Un error se salta los operadores.** `map` y `tap` solo trabajan con valores. Si la respuesta es un 401, el error viaja directo hasta `error`, sin pasar por ellos. Para atraparlo antes está `catchError`, que es lo que usa el interceptor de errores de la tarea.

Los operadores que vas a encontrar en código real:

| Operador | Qué hace |
|---|---|
| `map` | transforma cada valor |
| `filter` | deja pasar solo los valores que cumplen una condición |
| `tap` | hace algo con el valor sin cambiarlo |
| `catchError` | atrapa un error, y decide si lo deja seguir o lo reemplaza |
| `finalize` | corre al terminar, haya salido bien o mal |
| `debounceTime` | espera a que pase un tiempo sin valores nuevos, y entrega el último |
| `distinctUntilChanged` | descarta un valor si es igual al anterior |
| `switchMap` | con cada valor lanza otra petición; si llega un valor nuevo antes de la respuesta, cancela la anterior |

**Tres de ellos juntos: un buscador.** Cada tecla que se escribe en un campo es un valor de `valueChanges`, y la cadena decide cuándo preguntarle a tu API:

```ts
this.buscar.valueChanges.pipe(
  debounceTime(300),
  distinctUntilChanged(),
  switchMap(texto => this.avisosService.listar(texto))
)
```

Se verificó con tu API: al escribir "curso" de corrido sale **una sola** petición, `?q=curso`, no cinco. Y con una API lenta, al llegar un texto nuevo la búsqueda anterior se canceló: el navegador la marcó como abortada y a la pantalla solo llegó la respuesta del texto más reciente. Una promesa no se puede cancelar así, y esa es una de las razones por las que Angular usa Observables. El extra C de la tarea construye el buscador completo.

**Los Subjects.** Un `Subject` es un `Observable` al que tú le mandas valores con `next()`. El que usas hoy es un **`BehaviorSubject`**, que además **recuerda el último valor**: quien se suscribe tarde recibe el valor actual al instante. Por eso sirve para la sesión: cualquier componente que aparezca después sabe si hay alguien dentro.

**Desuscribirse.** Un `Observable` que emite una vez y termina, como el de HttpClient, se limpia solo. Uno que sigue vivo, como un `BehaviorSubject`, sigue avisando mientras alguien esté suscrito, aunque el componente ya no esté en pantalla. Hay dos formas de no dejar suscripciones colgadas: el pipe `async` en la plantilla, que se desuscribe solo cuando el componente se va (es el que usas hoy), o desuscribirse a mano en `ngOnDestroy()`.

---

## 19. La sesión: BehaviorSubject y sessionStorage

Varias partes de la pantalla necesitan saber si hay alguien dentro: el formulario de entrar, el de aviso nuevo, los botones de borrar. Esa información vive en un solo lugar, el `SesionService`:

```ts
private sesionSubject = new BehaviorSubject<Sesion | null>(this.leerGuardada());
readonly sesion$ = this.sesionSubject.asObservable();
```

Al entrar se llama `next(sesion)`; al salir, `next(null)`; y todas las partes de la pantalla que leen `sesion$` se actualizan en la misma detección de cambios. `asObservable()` expone solo la parte de leer: desde fuera del servicio nadie puede llamar a `next()`.

En la plantilla se lee con el pipe `async`:

```html
<form *ngIf="sesion.sesion$ | async"> ... </form>
```

El token se guarda en `sessionStorage`. La diferencia con `localStorage`, el que usaba el probador de la sesión 5:

| | `sessionStorage` | `localStorage` |
|---|---|---|
| Sobrevive a recargar la página | sí | sí |
| Sobrevive a cerrar la pestaña | no | sí |
| Lo comparten otras pestañas | no | sí |

Para un token, `sessionStorage` es la opción prudente: cerrar la pestaña es cerrar la sesión. Es la elección de un sistema real en producción.

**¿Y la cookie?** Guardar el token donde JavaScript lo puede leer tiene un costo: si alguien logra meter un script en tu página (un ataque XSS), ese script también puede leer `sessionStorage`. La alternativa es que la sesión viva en una **cookie `HttpOnly`**, que el navegador guarda y JavaScript no puede leer. Sanctum tiene ese modo para aplicaciones de una sola página que comparten dominio con su API: la sesión va en la cookie y ya no hay token que guardar.

La cookie trae su propio riesgo: el navegador la manda sola, así que otra página podría provocar una petición a tu API con ella (un ataque CSRF). Laravel lo frena con una segunda cookie, `XSRF-TOKEN`, cuyo valor hay que devolverle en el encabezado `X-XSRF-TOKEN`. Angular ya hace esa parte por su cuenta: `HttpClient` lee la cookie `XSRF-TOKEN` y manda `X-XSRF-TOKEN` en las peticiones que escriben (no en `GET` ni en `HEAD`) y solo a direcciones relativas, como `/api/avisos`. Se revisó en el código de Angular 16.2 y en el de Laravel 12: los dos usan esos nombres de fábrica.

| | Token en `sessionStorage` (el de hoy) | Cookie de Sanctum |
|---|---|---|
| JavaScript lo puede leer | sí, y un script inyectado también | no |
| Viaja solo en cada petición | no: lo pega tu interceptor | sí, y por eso existe el `X-XSRF-TOKEN` |
| Sirve para una app móvil o un frontend en otro dominio | sí | no: pide que la pantalla y la API compartan dominio |

En el curso se usa el token porque es el de la sesión 5, y porque sirve igual para una pantalla en otro dominio y para una app móvil que llamen a la misma API.

---

## 20. Formularios: por plantilla y reactivos

Angular tiene dos maneras de hacer formularios.

**Por plantilla**, la de la clase: el valor vive en una propiedad y el formulario en la plantilla.

```html
<input name="email" [(ngModel)]="email">
```

```ts
email = '';
```

Necesita `FormsModule`. Es cómoda para formularios cortos, como el de entrar.

**Reactivos**, los de un sistema real: el formulario entero vive en la clase, con sus reglas.

```ts
form = this.fb.group({
  titulo: ['', [Validators.required, Validators.maxLength(120)]],
  contenido: ['', Validators.required],
  categoria_id: [null as number | null, Validators.required]
});

constructor(private fb: NonNullableFormBuilder) { }
```

```html
<form [formGroup]="form" (ngSubmit)="guardar()">
  <input formControlName="titulo">
  <span *ngIf="form.controls.titulo.touched && form.controls.titulo.hasError('required')">Escribe un título.</span>
```

Necesita `ReactiveFormsModule`. Cada campo es un `FormControl`, el conjunto es un `FormGroup`, y las reglas son `Validators`: `Validators.required` y `Validators.maxLength(120)` son tus `'required'` y `'max:120'` de Laravel, del lado de la pantalla. `form.invalid` dice si algo no cumple, `markAllAsTouched()` enciende los mensajes de todos los campos, `getRawValue()` entrega los valores y `reset()` limpia.

Un sistema real usa formularios reactivos en decenas de archivos, casi siempre con `NonNullableFormBuilder` y con los campos de Angular Material. En la tarea conviertes el formulario de aviso nuevo a este estilo.

**Las reglas de la pantalla son comodidad.** Avisan antes de enviar y ahorran una petición, pero quien llame a tu API sin tu pantalla no pasa por ellas. Tu `validate()` las vuelve a revisar, y el 422 sigue existiendo: se verificó quitando `Validators.maxLength(120)` y mandando un título largo, y tu API respondió `The titulo field must not be greater than 120 characters.`

---

## 21. El interceptor, y la cadena de interceptores

En la sesión 3 un middleware se paraba entre la petición y tu controlador, y podía dejarla pasar, cortarla o cambiarla. Un interceptor hace lo mismo **del otro lado**: se para entre tu código y la red.

```ts
intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
  const token = this.sesion.token;

  if (token) {
    request = request.clone({
      setHeaders: { Authorization: `Bearer ${token}` }
    });
  }

  return next.handle(request);
}
```

- Cada petición que sale de la aplicación pasa por `intercept()`.
- Una petición de Angular **no se modifica**: se clona con el cambio. Por eso `request = request.clone(...)`.
- `next.handle(request)` la deja seguir, igual que `$next($request)` en tu middleware de Laravel.

Se registra en el módulo:

```ts
providers: [
  { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true }
]
```

`multi: true` dice que puede haber varios y este se suma a la lista.

**En cadena.** Un sistema real tiene al menos dos: el que pega el token y uno que atiende los errores. Cuando hay varios, la petición los recorre **en el orden en que están en `providers`**, y la respuesta los recorre **al revés** (verificado con los dos interceptores de la tarea). Es la cebolla del middleware de Laravel: `$next($request)` devuelve la respuesta por el mismo camino. Por eso el de errores, que actúa sobre lo que regresa, puede ver el 401 de tu API, olvidar la sesión y dejar que el error siga hasta el componente.

**La palabra del encabezado depende del backend.** Sanctum pide `Bearer`. Django REST Framework, con su autenticación por token, pide `Token`. Se verificó: si a tu API de Laravel le mandas `Authorization: Token ...`, responde 401 como si no hubieras mandado nada.

---

## 22. Componentes que hablan entre sí: @Input y @Output

Una aplicación es un árbol de componentes: `AppComponent` arriba, y dentro de él los demás. Entre padre e hijo, la regla es fija: **los datos bajan, los eventos suben**.

```ts
export class TarjetaAvisoComponent {
  @Input({ required: true }) aviso!: Aviso;       // entra desde el padre
  @Input() puedeBorrar = false;
  @Output() borrar = new EventEmitter<Aviso>();   // sale hacia el padre
}
```

```html
<!-- el padre, la lista -->
<app-tarjeta-aviso *ngFor="let aviso of avisos"
  [aviso]="aviso"
  (borrar)="borrar($event)">
</app-tarjeta-aviso>
```

- **`@Input`** es un dato que el padre le pasa al hijo con `[ ]`. Es el `@props` de tu `<x-tarjeta-post :post="$post">` de Blade. `required: true` es de Angular 16: si el padre no lo pasa, no compila. El `!` le dice a TypeScript que el valor llega aunque no se vea en la clase.
- **`@Output`** es un evento que el hijo emite con `borrar.emit(aviso)` y el padre escucha con `( )`. `$event` es lo que emitió. Es el `dispatch` de los eventos de Livewire.

El hijo no cambia los datos del padre directamente: avisa, y el padre decide. En la clase lo usas con el formulario de aviso nuevo, que emite `creado` para que la lista se recargue; en la tarea, con la tarjeta.

---

## 23. El proxy y el mismo origen

Tu servicio pide `/api/avisos`, sin dominio ni puerto. La petición va al mismo servidor que entregó la aplicación, el del 4200, y ese servidor la reenvía a tu Laravel en el 8000. Lo dice `proxy.conf.json`:

```json
{
  "/api": {
    "target": "http://127.0.0.1:8000",
    "secure": false,
    "changeOrigin": true
  }
}
```

¿Por qué no llamar directo al 8000? Por lo que viste con el probador en la sesión 5: el navegador trata el 4200 y el 8000 como **orígenes distintos**, y en Codespaces el túnel bloquea las peticiones entre orígenes aunque Laravel las permita. Con el proxy, el navegador solo habla con un origen, y el reenvío ocurre dentro del contenedor, donde no hay túnel.

El servidor de desarrollo también revisa desde qué dirección le llegan las peticiones. Por eso el `angular.json` del curso trae `allowedHosts` con el dominio de Codespaces: sin esa línea, la página puede cargar y la recarga en vivo no se conecta. Si alguna vez ves `Invalid Host header`, es eso.

En producción no hay servidor de desarrollo. La aplicación compilada son archivos estáticos que sirve cualquier servidor web, y la dirección de la API suele venir de un archivo de entorno (`environment.ts`). Un sistema real usa una dirección absoluta y le pide a su API que permita el origen del frontend.

---

## 24. El router: varias pantallas sin recargar

Tu aplicación tiene una sola pantalla. Una aplicación real tiene decenas, y el **router** decide cuál se ve según la dirección, sin recargar la página.

```ts
export const sesionGuard: CanActivateFn = () =>
  inject(SesionService).token ? true : inject(Router).parseUrl('/entrar');

const routes: Routes = [
  { path: '', component: AvisosListaComponent },
  { path: 'entrar', component: EntrarComponent },
  { path: 'nuevo', component: AvisoNuevoComponent, canActivate: [sesionGuard] },
  {
    path: 'reportes',
    loadChildren: () => import('./reportes/reportes.module').then(m => m.ReportesModule)
  }
];
```

Este código se compiló en un proyecto como el tuyo, con el módulo de rutas registrado.

| Angular | Laravel |
|---|---|
| el arreglo `routes` | `routes/web.php` |
| `<router-outlet>` en la plantilla | `@yield('content')` del layout |
| `routerLink="/nuevo"` en un enlace | `route('...')`, pero sin recargar la página |
| `canActivate: [sesionGuard]` | `->middleware('auth')` |
| `loadChildren` | sin pariente directo |

`loadChildren` es la **carga diferida**: esa parte de la aplicación queda en un archivo aparte (al compilar se verificó que salió un archivo propio para `reportes`) y el navegador solo la baja cuando alguien entra ahí. Un sistema real divide así sus áreas.

Un **guard** corre en el navegador. Igual que esconder un botón, ordena la pantalla, pero **no protege nada**: quien sepa la dirección de tu API puede pedirle lo que quiera. Quien protege sigue siendo tu API.

---

## 25. Módulos y standalone

Hoy tus componentes viven en un módulo: se declaran en `declarations` de `app.module.ts`. Angular tiene otro estilo, los componentes **standalone**, que no necesitan módulo porque traen sus propias dependencias:

```ts
@Component({
  selector: 'app-demo',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './demo.component.html',
  styleUrls: ['./demo.component.css']
})
export class DemoComponent { }
```

Así lo genera Angular 16 con `ng generate component demo --standalone` (verificado). Quien lo usa lo **importa**, no lo declara: `imports: [DemoComponent]`. Desde la versión 19, standalone es el valor por defecto.

Un sistema real escrito en la 16 suele mezclar los dos estilos. Si ves `standalone: true`, las dependencias de ese componente están en su propio `imports`, no en el módulo.

---

## 26. Angular Material

Angular Material es la biblioteca de componentes visuales de Google para Angular: campos, tablas, botones, diálogos, avisos, ya diseñados y accesibles. Es lo que en tu blog hacen Tailwind y tus componentes de Blade.

```html
<mat-form-field>
  <mat-label>Título</mat-label>
  <input matInput formControlName="titulo">
  <mat-error>Escribe un título.</mat-error>
</mat-form-field>

<button mat-raised-button color="primary">Crear aviso</button>
```

Se reconoce por las etiquetas `<mat-...>` y los atributos `mat...`. Cada pieza trae su módulo (`MatInputModule`, `MatButtonModule`) que hay que importar. Su versión va con la de Angular: Material 16 con Angular 16. Casi siempre aparece dentro de formularios reactivos.

En esta sesión no se instala; se explica para que lo reconozcas.

---

## 27. Dónde mirar cuando algo falla

| Dónde | Qué aparece |
|---|---|
| La terminal de `npm start` | los errores de compilación: `TS2339`, `TS2532`, `NG8002`. Mientras haya uno, el navegador no recibe la versión nueva |
| La consola del navegador (F12) | los errores al correr: `NullInjectorError`, un `undefined` inesperado. También tus `console.log` |
| La pestaña Red (F12) | lo que respondió tu API: 401, 403, 422, 504, con el cuerpo completo |

Dos herramientas más:

- `{{ aviso | json }}` en la plantilla pinta el objeto entero. Es la forma más rápida de ver qué llegó de verdad.
- **Angular DevTools** es una extensión del navegador que muestra el árbol de componentes y el valor de sus propiedades, y trae un perfilador que mide cuánto tarda cada detección de cambios. Sirve mucho al abrir un proyecto que no escribiste. Funciona con la aplicación en modo de desarrollo, como la de `npm start`, no con la compilada para producción. Su pestaña del árbol de inyectores es de Angular 17 en adelante: en la 16 no aparece.

---

## 28. Lo que vas a encontrar en un sistema real

Un sistema real en producción escrito con esta misma versión se organiza con las mismas piezas que construiste hoy, a mayor escala:

| Hoy | En un sistema real |
|---|---|
| `AvisosService` | un servicio por recurso de la API, en una carpeta de servicios |
| `SesionService` con `BehaviorSubject` y `sessionStorage` | lo mismo, con más datos del usuario |
| `AuthInterceptor` | el mismo, más un interceptor de errores |
| un `AppModule` y una pantalla | varios módulos, rutas con carga diferida y guards |
| formularios por plantilla | formularios reactivos con `NonNullableFormBuilder` |
| estilos del curso | Angular Material |
| `/api` con proxy | una dirección absoluta en el archivo de entorno |
| cuatro componentes | decenas, con cientos de `*ngIf` y más de cien `@Input` y `@Output` |

Cuando abras un proyecto así, el orden para entenderlo es: `app.module.ts` y el módulo de rutas para ver qué pantallas existen, la carpeta de servicios para ver qué le pide a la API, los interceptores para ver qué le pasa a cada petición, y después los componentes.

---

## 29. Para seguir aprendiendo

Lo que sigue es gratuito y sirve para lo que usas en este curso. Casi todo está en inglés; al final hay opciones de pago en español. Cuando leas documentación de Angular, fíjate en la versión: angular.dev es de la 22, que usa `@if`, `@for` y componentes standalone (sección 4). La documentación de tu versión, la 16, sigue publicada en el repositorio de Angular.

| Para | Recurso | Qué tiene |
|---|---|---|
| TypeScript, viniendo de PHP | [TypeScript for Java/C# Programmers](https://www.typescriptlang.org/docs/handbook/typescript-in-5-minutes-oop.html) | la guía oficial para quien ya programa con clases: el tipado estructural y los tipos que se borran de la sección 6 |
| TypeScript, el estrechamiento | [Narrowing](https://www.typescriptlang.org/docs/handbook/2/narrowing.html) | `typeof`, `in`, predicados de tipo y uniones discriminadas: los pasos 4, 8 y 9 del ejercicio |
| TypeScript, probar sin instalar | [Playground](https://www.typescriptlang.org/play) | escribes TypeScript en el navegador y ves el JavaScript que sale; elige la versión 5.1 para que se comporte como tu proyecto |
| TypeScript, practicar | [typescript-exercises](https://typescript-exercises.github.io/) y los [tutoriales gratuitos de Total TypeScript](https://www.totaltypescript.com/tutorials) | ejercicios cortos que se revisan en el mismo editor, con su solución |
| Angular 16 | [La documentación de la 16](https://github.com/angular/angular/tree/16.2.x/aio/content), en el repositorio de Angular | la de tu versión, con módulos y `*ngIf` |
| Angular, un buscador completo | [Tour of Heroes, parte 6](https://github.com/angular/angular/blob/16.2.x/aio/content/tutorial/tour-of-heroes/toh-pt6.md) | HttpClient con manejo de errores, y el buscador con `debounceTime`, `distinctUntilChanged` y `switchMap` del extra C de la tarea |
| Angular, depurar | [Angular DevTools](https://angular.dev/tools/devtools) | la extensión de la sección 27 |
| RxJS, un operador a la vez | [Learn RxJS](https://www.learnrxjs.io/learn-rxjs/operators) | cada operador con ejemplos, y los más usados marcados |
| RxJS, cuál operador buscas | [El árbol de decisión de operadores](https://rxjs.dev/operator-decision-tree) | respondes unas preguntas y te dice qué operador necesitas |
| RxJS, verlo en el tiempo | [RxMarbles](https://rxmarbles.com/) | diagramas de canicas interactivos: mueves los valores y ves qué sale |

Si prefieres video en español, DevTalles, Platzi y EDteam tienen cursos de pago de Angular y de TypeScript. Antes de pagar, revisa con qué versión de Angular se grabaron, o que expliquen los módulos (`NgModule`): tu proyecto los usa.

---

## Glosario

| Término | Qué es |
|---|---|
| **SPA** | Aplicación de una sola página: el HTML llega una vez y JavaScript cambia la pantalla sin recargar |
| **AngularJS** | La versión 1.x, de 2010. Un framework distinto de Angular, sin soporte desde 2022 |
| **TypeScript** | JavaScript con tipos. Se traduce a JavaScript al compilar, y los tipos se borran |
| **Interfaz** | La forma de un objeto en TypeScript. Una promesa de cómo viene un dato, que no se revisa en tiempo de ejecución |
| **Genérico** | Un tipo con parámetro: `Observable<Aviso[]>`, `get<T>()` |
| **`unknown`** | El tipo de algo que todavía no se revisa. Lo contrario de `any`: no deja usarlo hasta estrecharlo |
| **Guardián de tipo** | Una función que devuelve `x is T`: revisa un valor al correr y le confirma su tipo a TypeScript |
| **Unión discriminada** | Una unión de objetos con un campo en común (`tipo`) que dice cuál de ellos es |
| **Decorador** | La marca con `@` sobre una clase o propiedad que le dice a Angular qué es |
| **CLI** | La herramienta de línea de comandos de Angular: `ng serve`, `ng generate`, `ng build` |
| **webpack** | El empaquetador con el que compila la CLI de Angular hasta la 16: junta todo en unos pocos archivos |
| **webpack-dev-server** | El servidor de desarrollo de webpack: el que atiende el 4200 cuando corres `npm start` |
| **esbuild** | Un compilador de JavaScript y TypeScript. Con él, tu proyecto compiló en 3.5 s contra 9.6 s con webpack; Angular lo usa de fábrica desde la 17 |
| **Vite** | El servidor de desarrollo y empaquetador que usa Laravel para tu CSS y tu JavaScript, y Angular desde la 17 |
| **Compilador de Angular** | Lo que convierte cada plantilla en una función de JavaScript |
| **Componente** | Un pedazo de pantalla: clase, plantilla y estilos |
| **Plantilla** | El HTML del componente, con las marcas de Angular |
| **Selector** | El nombre de etiqueta con el que se usa un componente |
| **Ciclo de vida** | Los métodos que Angular llama en momentos fijos: `ngOnInit`, `ngOnDestroy` |
| **Encapsulación de estilos** | El CSS de un componente solo le aplica a él |
| **Interpolación** | `{{ }}`: pinta un valor de la clase en la plantilla |
| **Enlace de propiedad** | `[atributo]="valor"`: un dato que entra a la pantalla |
| **Enlace de evento** | `(evento)="metodo()"`: la pantalla llama a la clase |
| **Directiva** | Una instrucción en la plantilla: estructural (`*ngIf`, `*ngFor`) o de atributo (`[class.x]`, `ngClass`) |
| **Pipe** | Transforma un valor al pintarlo: `date`, `uppercase`, `json`, `async` |
| **Detección de cambios** | El recorrido con el que Angular vuelve a evaluar los enlaces y actualiza la pantalla |
| **zone.js** | La biblioteca que le avisa a Angular cuando termina algo asíncrono |
| **Signal** | Un valor que avisa exactamente cuándo cambia. Vista previa en la 16, estable después |
| **Módulo** (`NgModule`) | El registro de piezas de la aplicación |
| **Standalone** | Un componente que no necesita módulo: trae sus dependencias en su propio `imports` |
| **Servicio** | Una clase sin pantalla que hace trabajo para los componentes |
| **Inyector** | Lo que crea y entrega los servicios que piden los constructores |
| **Inyección de dependencias** | Pedir lo que necesitas en el constructor (o con `inject()`) y que el framework te lo entregue |
| **RxJS** | La biblioteca de `Observable`, operadores y Subjects que usa Angular |
| **Observable** | Una respuesta que llega después. No hace nada hasta que alguien se suscribe |
| **`subscribe`** | Decir qué hacer cuando llegue la respuesta y qué hacer si falla |
| **Operador** | Una función que se pone en `pipe()` para transformar lo que pasa: `map`, `tap`, `catchError` |
| **BehaviorSubject** | Un `Observable` al que le mandas valores con `next()` y que recuerda el último |
| **`debounceTime`** | Un operador que espera un tiempo sin valores nuevos y entrega el último |
| **`switchMap`** | Un operador que lanza otro Observable por cada valor, y cancela el anterior si llega uno nuevo |
| **Formulario reactivo** | Un formulario que vive en la clase: `FormGroup`, `FormControl` y `Validators` |
| **Interceptor** | Una pieza por la que pasa cada petición HTTP que sale de la aplicación |
| **XSRF (o CSRF)** | Un ataque en el que otra página provoca una petición con tus cookies. Laravel y Angular lo frenan con la cookie `XSRF-TOKEN` |
| **`@Input` y `@Output`** | Un dato que entra desde el padre y un evento que sale hacia él |
| **`EventEmitter`** | Lo que un componente usa para emitir un `@Output` |
| **Router** | Lo que decide qué componente se ve según la dirección, sin recargar la página |
| **Guard** | Una función que decide si se puede entrar a una ruta. Ordena la pantalla, no protege |
| **Carga diferida** | Una parte de la aplicación que se baja solo cuando alguien entra ahí (`loadChildren`) |
| **Angular Material** | La biblioteca de componentes visuales de Google para Angular |
| **Proxy** | El reenvío de `/api` del servidor de Angular a tu Laravel |
| **Recarga en vivo** | Guardar un archivo y ver el cambio en el navegador sin recargar a mano |
