# Ejercicio 1 · TypeScript: por qué existe, y cómo se escribe

Objetivo: entender qué problema resolvió TypeScript, cómo surgió y por qué Angular está escrito en él; y escribir TypeScript de verdad, con los datos de tu API, hasta que el compilador deje de quejarse.

Tiempo estimado: la parte A se lee en 15 minutos (en clase se explica con los slides). La parte B son siete pasos: **los pasos 1 a 3 se hacen en clase** (unos 8 minutos) y **los pasos 4 a 7 van en la tarea** (unos 35 minutos). La parte C es opcional: tres pasos más, de unos 45 minutos, que cuentan como extra.

---

## Parte A · Por qué existe TypeScript

### A.1 El problema: JavaScript no avisa

JavaScript nació en 1995, en Netscape. Lo escribió Brendan Eich en unos diez días, para cosas pequeñas: validar un formulario, mover una imagen, abrir una ventana. En JavaScript una variable puede guardar cualquier cosa, y nadie revisa nada hasta que el código corre.

La base de hoy te trajo un archivo con tres errores a propósito, `frontend/practica-ts/sin-tipos.js`. Lo esencial:

```js
function vencimiento(fecha, dias) {
  return fecha + dias;
}

function titulares(lista) {
  return lista.map(aviso => aviso.title.toUpperCase());
}

function categorias(lista) {
  return lista.map(aviso => aviso.categoria.nombre);
}
```

Esto es lo que imprime al correrlo (copiado de la terminal):

```
Vence: 2026-09-10T10:00:00-06:003
TypeError: Cannot read properties of undefined (reading 'toUpperCase')
```

Son tres maneras distintas de fallar:

1. **El error silencioso.** `fecha` es texto y `dias` es número; texto más número es texto. La fecha sale mal y el programa sigue como si nada.
2. **El error que avisa tarde.** El campo se llama `titulo`, no `title`. JavaScript no se da cuenta hasta que esa línea corre, y cuando corre detiene todo lo demás. En una aplicación de Angular, eso pasa en el navegador de quien usa la pantalla.
3. **El error escondido.** El segundo aviso no trae categoría, así que `categorias()` también iba a tronar. Pero nunca llegó a correr: el programa ya se había caído. Ese error sigue ahí, esperando.

En un archivo de 30 líneas los encuentras en un minuto. En una aplicación de cientos de archivos, con varias personas cambiándola, un nombre de campo que cambió en la API rompe una pantalla que nadie abrió ese día.

PHP pasó por lo mismo. En PHP 5 no podías decir que un parámetro era un número; PHP 7 (2015) agregó los tipos en parámetros y retornos, PHP 7.4 las propiedades tipadas y PHP 8 los tipos de unión. Tú ya escribes PHP con tipos: sabes lo que ahorran. JavaScript nunca los tuvo.

### A.2 Cómo surgió

A partir de 2004, aplicaciones como Gmail y Google Maps demostraron que el navegador podía correr programas completos, no solo adornos. Los proyectos de JavaScript pasaron de cientos a cientos de miles de líneas, y el lenguaje no ayudaba a mantenerlos.

Hubo varias respuestas. CoffeeScript (2009) cambió la sintaxis pero no agregó tipos. Google presentó Dart (2011), un lenguaje nuevo que aspiraba a reemplazar a JavaScript. Facebook hizo Flow (2014), un revisor de tipos para JavaScript.

La que se quedó fue la de Microsoft. Sus propios equipos escribían aplicaciones grandes en JavaScript, y encargaron el problema a **Anders Hejlsberg**, autor de Turbo Pascal y arquitecto de Delphi y de C#. El resultado se presentó el **1 de octubre de 2012** como TypeScript 0.8, de código abierto desde el primer día. La **1.0** salió en abril de 2014.

| Fecha | Versión | Qué trajo |
|---|---|---|
| octubre de 2012 | 0.8 | la presentación pública |
| abril de 2014 | 1.0 | la primera versión estable |
| julio de 2015 | 1.5 | los decoradores, a pedido del equipo de Angular (sección A.4) |
| junio de 2023 | 5.1 | la que usa Angular 16, y la que corre en tu proyecto |
| 2026 | 7.0 | la más reciente publicada |

Las fechas son las de publicación en el registro de npm.

**Tres decisiones de diseño explican por qué ganó:**

1. **Es un superconjunto de JavaScript.** Todo JavaScript válido es TypeScript válido. No hay que reescribir nada para empezar: se cambia la extensión del archivo. El paso 2 del ejercicio lo demuestra.
2. **Se compila a JavaScript normal.** El resultado corre en cualquier navegador y en Node, sin nada extra. Los tipos se borran al compilar: no cuestan nada al correr.
3. **Es gradual.** Puedes tener partes con tipos y partes sin ellos, y agregarlos poco a poco. Lo que todavía no tiene tipo se marca como `any`, que apaga la revisión ahí.

Y una cuarta, que no es del lenguaje sino de las herramientas: como el compilador conoce los tipos, el editor puede autocompletar, avisar mientras escribes y renombrar un campo en todo el proyecto. VS Code, que también es de Microsoft, usa el motor de TypeScript incluso para entender JavaScript simple.

**Cinco errores comunes que JavaScript acepta sin avisar.** Con `interface Aviso { id: number; titulo: string; publicado: boolean; }` y `const idDeLaUrl = '7';`, las cinco líneas de la izquierda corren en Node sin un solo error. Las cinco las marca el TypeScript 5.1.6 de tu proyecto antes de correr:

| Lo que escribes | Lo que hace JavaScript | Lo que dice TypeScript |
|---|---|---|
| `console.log(aviso.titluo);` | Imprime `undefined`: el título sale vacío | `error TS2551: Property 'titluo' does not exist on type 'Aviso'. Did you mean 'titulo'?` |
| `aviso.publicado = 'no';` | `'no'` cuenta como verdadero: el aviso sale como publicado | `error TS2322: Type 'string' is not assignable to type 'boolean'.` |
| `avisos.find(a => a.id === idDeLaUrl)` | Da `undefined`: `7` y `'7'` no son iguales, así que nunca lo encuentra | `error TS2367: This comparison appears to be unintentional because the types 'number' and 'string' have no overlap.` |
| `crearAviso('Simulacro')`, si la función pide título y contenido | Arma el aviso con `contenido: undefined` | `error TS2554: Expected 2 arguments, but got 1.` |
| `const respuesta = fetch(url);` y después `respuesta.ok` | Da `undefined`: sin `await`, `respuesta` todavía es una promesa | `error TS2339: Property 'ok' does not exist on type 'Promise<Response>'.` |

El tercero es el más difícil de encontrar a mano: lo que llega de una URL siempre es texto, y en la pantalla solo ves que el aviso "no aparece".

### A.3 Tres diferencias con PHP que conviene saber

**1. Importa la forma, no el nombre.** En PHP un objeto es de un tipo porque su clase lo declara (`class X implements Y`). En TypeScript, cualquier objeto que tenga los campos de una interfaz la cumple, sin declararlo. Por eso el JSON de tu API "es" un `Aviso` en cuanto tiene `id`, `titulo` y `creado`. A esto se le llama tipado estructural.

**2. Los tipos no existen al correr.** Se borran al compilar. No puedes preguntar `instanceof Aviso` a una interfaz, y TypeScript no revisa los datos que llegan de tu API: revisa **tu código**. Si tu API cambia un campo, TypeScript no se entera.

**3. `any` apaga todo.** `JSON.parse()` y `respuesta.json()` devuelven `any`. Con `any` puedes escribir `aviso.title.toUpperCase()` y compila sin quejarse, aunque truene al correr. Se verificó: el compilador termina sin errores y Node lanza el mismo `TypeError` del paso 1.

Las tres dicen lo mismo: **una interfaz es una promesa de cómo vienen los datos.** Dentro de tu código, TypeScript la hace cumplir. En la frontera con tu API, la cumples tú.

### A.4 Por qué Angular está escrito en TypeScript

AngularJS, el de 2010, estaba escrito en JavaScript. Cuando Google empezó a rehacerlo, en 2014, su equipo anunció **AtScript**: TypeScript más anotaciones para describir componentes y dependencias. En marzo de 2015, Google y Microsoft anunciaron que Angular 2 se escribiría en TypeScript, y que TypeScript agregaría lo que AtScript necesitaba: **los decoradores**, que llegaron en la 1.5, en julio de 2015. Angular 2 salió en septiembre de 2016, escrito en TypeScript, y todas las versiones siguientes también.

Lo que Angular obtiene de TypeScript, con evidencia de tu propio proyecto:

**1. Decoradores.** `@Component`, `@Injectable` y `@NgModule` le pegan información a tus clases, y el compilador de Angular la lee. El paso 7 construye uno pequeño para que veas en qué consiste.

**2. Inyección por tipo.** Cuando escribes `constructor(private avisosService: AvisosService, public sesion: SesionService)`, Angular sabe qué entregarte porque lee **los tipos** de esos parámetros. Esto es lo que el compilador escribió para tu lista (real, copiado del código compilado):

```js
this.ɵfac = function AvisosListaComponent_Factory(t) {
  return new (t || AvisosListaComponent)(ɵɵdirectiveInject(AvisosService), ɵɵdirectiveInject(SesionService));
};
```

Los tipos del constructor se volvieron instrucciones para el inyector. AngularJS, sin tipos, adivinaba las dependencias por el nombre del parámetro, y eso se rompía al comprimir el código para producción, porque la compresión cambia los nombres.

**3. Plantillas revisadas contra la clase.** El error `Property 'title' does not exist on type 'Aviso'` en un archivo `.html` existe porque el compilador cruza tu plantilla con los tipos de tu componente.

**4. Proyectos grandes.** Autocompletar, ir a la definición, renombrar en todo el proyecto y ver los errores al guardar. En un sistema con decenas de componentes, eso es lo que permite cambiar algo sin romper lo de al lado.

El `tsconfig.json` de tu aplicación de Angular trae `"strict": true` (la revisión más estricta) y `"experimentalDecorators": true` (los decoradores en la forma que usa Angular 16).

---

## Parte B · El ejercicio

La base te trajo la carpeta `frontend/practica-ts/`:

```
practica-ts/
├── tsconfig.json    la configuración del compilador para este ejercicio
├── sin-tipos.js     el JavaScript con los tres errores
└── .gitignore       deja fuera la carpeta salida/, que escribe el compilador
```

TypeScript ya está instalado: es el mismo que usa tu aplicación de Angular. Compruébalo dentro de `frontend/`:

```bash
npx tsc --version
```

Debe decir `Version 5.1.6`.

El `tsconfig.json` de la carpeta pide lo mismo que el de Angular: modo estricto y decoradores. `npx tsc -p practica-ts` compila todos los `.ts` de la carpeta y deja el JavaScript en `practica-ts/salida/`.

Todos los comandos se corren **dentro de `frontend/`**.

### Paso 1 · JavaScript sin tipos (en clase)

```bash
node practica-ts/sin-tipos.js
```

Sale la fecha mal (`Vence: 2026-09-10T10:00:00-06:003`) y después el `TypeError`. `Categorias` nunca se imprime.

Antes de seguir, responde: ¿cuál de los tres errores te avisó JavaScript, cuál no te avisó y cuál ni siquiera se ejecutó?

### Paso 2 · El mismo archivo, como TypeScript (en clase)

Cópialo con extensión `.ts`, sin cambiarle nada, y compílalo:

```bash
cp practica-ts/sin-tipos.js practica-ts/avisos.ts
npx tsc -p practica-ts
```

Salen seis errores como este:

```
practica-ts/avisos.ts(15,22): error TS7006: Parameter 'fecha' implicitly has an 'any' type.
```

Fíjate en dos cosas:

- **El archivo se aceptó tal cual.** No hubo error de sintaxis: JavaScript válido es TypeScript válido.
- **Todavía no encontró los tres errores.** Sin tipos, TypeScript no sabe qué es `fecha` ni qué trae `lista`, así que todo es `any`. Lo que te pide el modo estricto es justo eso: que digas qué es cada parámetro.

### Paso 3 · La forma de un aviso (en clase)

Deja `practica-ts/avisos.ts` así, con la interfaz y los tipos, **sin corregir todavía los errores**:

```ts
interface Aviso {
  id: number;
  titulo: string;
  categoria?: { id: number; nombre: string };
  creado: string;
}

const avisos: Aviso[] = [
  { id: 1, titulo: 'Cambio de horario en barandilla', categoria: { id: 1, nombre: 'Aviso' }, creado: '2026-09-10T10:00:00-06:00' },
  { id: 2, titulo: 'Curso de primeros auxilios', creado: '2026-09-09T09:00:00-06:00' }
];

function vencimiento(fecha: string, dias: number): Date {
  return fecha + dias;
}

function titulares(lista: Aviso[]): string[] {
  return lista.map(aviso => aviso.title.toUpperCase());
}

function categorias(lista: Aviso[]): string[] {
  return lista.map(aviso => aviso.categoria.nombre);
}

console.log('Vence:', vencimiento(avisos[0].creado, 3));
console.log('Titulares:', titulares(avisos));
console.log('Categorias:', categorias(avisos));
```

```bash
npx tsc -p practica-ts
```

Ahora aparecen los tres, **sin haber corrido nada**:

```
practica-ts/avisos.ts(14,3): error TS2322: Type 'string' is not assignable to type 'Date'.
practica-ts/avisos.ts(18,35): error TS2339: Property 'title' does not exist on type 'Aviso'.
practica-ts/avisos.ts(22,29): error TS18048: 'aviso.categoria' is possibly 'undefined'.
```

| Error | Lo que te dice |
|---|---|
| `TS2322` | Dijiste que `vencimiento` devuelve una fecha, y devuelve texto |
| `TS2339` | `Aviso` no tiene `title` |
| `TS18048` | La interfaz dice que la categoría puede no venir, y la usas como si siempre viniera |

Corrígelos:

```ts
function vencimiento(fecha: string, dias: number): Date {
  const dia = new Date(fecha);
  dia.setDate(dia.getDate() + dias);
  return dia;
}

function titulares(lista: Aviso[]): string[] {
  return lista.map(aviso => aviso.titulo.toUpperCase());
}

function categorias(lista: Aviso[]): string[] {
  return lista.map(aviso => aviso.categoria?.nombre ?? 'Sin categoría');
}
```

y cambia la primera línea de `console.log` para mostrar la fecha como texto:

```ts
console.log('Vence:', vencimiento(avisos[0].creado, 3).toISOString().slice(0, 10));
```

Compila y corre. El `&&` hace que solo corra si compiló sin errores:

```bash
npx tsc -p practica-ts && node practica-ts/salida/avisos.js
```

```
Vence: 2026-09-13
Titulares: [ 'CAMBIO DE HORARIO EN BARANDILLA', 'CURSO DE PRIMEROS AUXILIOS' ]
Categorias: [ 'Aviso', 'Sin categoría' ]
```

**Un detalle que enseña mucho.** Cambia el tipo que devuelve `vencimiento` de `Date` a `string` y deja el `return fecha + dias;` original: TypeScript ya no se queja, porque texto más número sí es texto. TypeScript no adivina tu intención: **revisa lo que declaras**. Por eso los tipos de retorno valen la pena.

Aquí termina la parte de clase. Los pasos 4 a 7 van en la tarea, en el mismo archivo.

### Paso 4 · Tipos que dicen exactamente qué se vale

Agrega al final de `avisos.ts`:

```ts
type Rol = 'admin' | 'editor' | 'lector';

interface Sesion {
  token: string;
  usuario: string;
  rol: Rol;
}

// La regla de create() de tu PostPolicy, del lado de la pantalla.
function puedeCrear(sesion: Sesion | null): boolean {
  if (sesion === null) {
    return false;
  }
  return sesion.rol === 'admin' || sesion.rol === 'editor';
}

const editor: Sesion = { token: 'abc', usuario: 'Editor de guardia', rol: 'editor' };
console.log('¿Puede crear el editor?', puedeCrear(editor));
console.log('¿Y sin sesión?', puedeCrear(null));
```

- `'admin' | 'editor' | 'lector'` es un **tipo de unión**: el rol solo puede ser uno de esos tres textos. Es tu columna `rol` de la sesión 3, escrita como tipo.
- `Sesion | null`: una sesión, o nada. Es el mismo tipo del `BehaviorSubject` de tu `SesionService`.
- El `if (sesion === null)` no es solo lógica: después de él, TypeScript **sabe** que `sesion` ya no es `null`. A eso se le llama estrechar el tipo.

Compila y corre: `true` y `false`.

**Rómpelo, uno por uno, y regrésalo:**

| Cambio | Lo que dice el compilador |
|---|---|
| `rol: 'jefe'` en `editor` | `error TS2322: Type '"jefe"' is not assignable to type 'Rol'.` |
| Quita el `if (sesion === null)` | `error TS18047: 'sesion' is possibly 'null'.` |
| Compara con `'administrador'` | `error TS2367: This comparison appears to be unintentional because the types 'Rol' and '"administrador"' have no overlap.` |

El último es un error que en PHP descubrirías cuando alguien reporte que el admin no puede crear avisos.

### Paso 5 · Genéricos: el sobre `data` de Laravel

Tu `PostResource` envuelve todo en `"data"`: una lista en `GET /api/avisos`, un aviso en `GET /api/avisos/1`. El sobre es el mismo; lo que trae adentro cambia. Eso es un **genérico**. Agrega:

```ts
interface Respuesta<T> {
  data: T;
}

function sacar<T>(respuesta: Respuesta<T>): T {
  return respuesta.data;
}

const muchos: Respuesta<Aviso[]> = { data: avisos };
const uno: Respuesta<Aviso> = { data: avisos[0] };

console.log('Cuántos:', sacar(muchos).length);
console.log('Uno:', sacar(uno).titulo);
```

`T` es un hueco que se llena al usarlo: en `muchos` vale `Aviso[]`, en `uno` vale `Aviso`, y `sacar()` devuelve exactamente eso. Corre: `Cuántos: 2` y `Uno: Cambio de horario en barandilla`.

**Rómpelo:** cambia `sacar(uno).titulo` por `sacar(uno).length`. El compilador responde `error TS2339: Property 'length' does not exist on type 'Aviso'.`: sabe que de `uno` sale un solo aviso, no una lista.

Es lo que escribes en tu `AvisosService`: `this.http.get<{ data: Aviso[] }>('/api/avisos')`. El `<...>` es el mismo hueco.

### Paso 6 · Una clase como las de Angular, contra tu API

Con `composer run dev` corriendo, agrega al final:

```ts
class AvisosRepositorio {
  private pedidas = 0;

  constructor(private base: string) { }

  async listar(): Promise<Aviso[]> {
    this.pedidas++;
    const respuesta = await fetch(`${this.base}/api/avisos`);
    // json() devuelve any: el "as" es una promesa a TypeScript, no una revision.
    const cuerpo = await respuesta.json() as Respuesta<Aviso[]>;
    return cuerpo.data;
  }

  get veces(): number {
    return this.pedidas;
  }
}

async function main(): Promise<void> {
  const repo = new AvisosRepositorio('http://127.0.0.1:8000');
  const deTuApi = await repo.listar();
  console.log('Desde tu API:', titulares(deTuApi));
  console.log('Sus categorías:', categorias(deTuApi));
  console.log('Peticiones hechas:', repo.veces);
}

main();
```

Compila y corre: ahora salen **tus** avisos, pedidos a tu API desde Node, con las mismas funciones del paso 3.

Lo que tiene esta clase, y dónde lo vas a ver en Angular:

| Aquí | En Angular |
|---|---|
| `constructor(private base: string)` | `constructor(private http: HttpClient)`: la promoción de propiedades |
| `private pedidas = 0` | una propiedad que la plantilla no puede leer |
| `get veces()` | un valor que se lee como propiedad pero se calcula |
| `Promise<Aviso[]>` y `await` | `Observable<Aviso[]>` y `subscribe` |
| `respuesta.json() as Respuesta<Aviso[]>` | `http.get<{ data: Aviso[] }>`: una promesa sobre la forma, sin revisión |

Una `Promise` es un valor que llega después, **una sola vez**. Un `Observable` es la misma idea con operadores, y puede traer varios valores. `fetch` devuelve la primera; `HttpClient` de Angular, el segundo.

**Rómpelo:** en `main()`, cambia `repo.veces` por `repo.pedidas`. El compilador responde `error TS2341: Property 'pedidas' is private and only accessible within class 'AvisosRepositorio'.`, el mismo error que ves cuando una plantilla lee algo privado.

**Y mira `any` en acción:** crea `practica-ts/cualquiera.ts` con esto:

```ts
const texto = '{"id": 1, "titulo": "Cambio de horario"}';
const aviso = JSON.parse(texto);
console.log(aviso.title.toUpperCase());
```

Compila: **cero errores**. Córrelo con `node practica-ts/salida/cualquiera.js`: el mismo `TypeError` del paso 1. `JSON.parse` devuelve `any`, y con `any` TypeScript deja de revisar. Bórralo al terminar (`rm practica-ts/cualquiera.ts`).

### Paso 7 · Un decorador: en qué consiste `@Component`

Crea `practica-ts/decorador.ts`:

```ts
const registro = new Map<string, Function>();

function Pieza(nombre: string) {
  return function (clase: Function): void {
    registro.set(nombre, clase);
  };
}

@Pieza('lista-de-avisos')
class ListaDeAvisos { }

@Pieza('formulario-de-entrar')
class FormularioDeEntrar { }

console.log('Piezas registradas:', [...registro.keys()]);
console.log('La clase detrás de lista-de-avisos:', registro.get('lista-de-avisos')?.name);
```

```bash
npx tsc -p practica-ts && node practica-ts/salida/decorador.js
```

```
Piezas registradas: [ 'lista-de-avisos', 'formulario-de-entrar' ]
La clase detrás de lista-de-avisos: ListaDeAvisos
```

`Pieza` es una función que recibe una clase y guarda información sobre ella. La `@` la aplica a la clase de abajo en el momento en que se define. Nadie creó un `ListaDeAvisos` y aun así quedó registrado.

`@Component({ selector: 'app-avisos-lista', ... })` es lo mismo, con más datos: guarda el selector, la plantilla y los estilos de tu clase, y el compilador de Angular los usa para armar la aplicación. Ya no es magia: es una función.

---

## Checkpoint

- `npx tsc -p practica-ts` termina **sin errores**.
- `node practica-ts/salida/avisos.js` imprime la fecha, los titulares, las categorías, las dos respuestas de `puedeCrear`, el genérico y **los avisos de tu API**.
- `node practica-ts/salida/decorador.js` imprime las dos piezas registradas.

En tu PR van `practica-ts/avisos.ts` y `practica-ts/decorador.ts`. La carpeta `salida/` no viaja: la deja fuera el `.gitignore`.

---

## Parte C · Para ir más lejos: la frontera con tu API

Opcional. Cuenta como extra en la tarea (unos 45 minutos).

La parte A terminó con una idea: **una interfaz es una promesa de cómo vienen los datos.** Dentro de tu código, TypeScript la hace cumplir; en la frontera con tu API, la cumples tú. Los pasos 8 a 10 son tres herramientas para esa frontera: revisar lo que llega, describir en qué estado va una petición y armar los tipos de lo que mandas a partir de los que ya tienes.

Van en un archivo nuevo, `practica-ts/frontera.ts`. Empieza así:

```ts
// La linea de abajo hace que este archivo tenga sus propios nombres y no choque con avisos.ts.
export {};

// La misma forma de tu app de Angular (src/app/modelos/aviso.ts).
interface Aviso {
  id: number;
  titulo: string;
  contenido: string;
  publicado: boolean;
  categoria?: { id: number; nombre: string };
  autor?: string;
  creado: string;
}
```

¿Por qué el `export {};`? `avisos.ts` y `decorador.ts` son archivos sueltos, y lo que declara uno lo ven los demás de la carpeta. Un archivo con `export` o `import` es un **módulo**: sus nombres son solo suyos. Sin esa línea, este `Aviso` y el de `avisos.ts` se juntarían en una sola interfaz, y `avisos.ts` dejaría de compilar (está en la tabla del final). Todos los archivos de tu app de Angular son módulos; por eso empiezan con `import`.

### Paso 8 · Revisar lo que llega: `unknown` y un guardián de tipo

En el paso 6 escribiste `respuesta.json() as Respuesta<Aviso[]>`, y en Angular escribes `http.get<{ data: Aviso[] }>()`. Las dos son promesas. La guía de HttpClient de Angular 16 lo dice así: ese tipo es una revisión al compilar y no garantiza lo que responda el servidor. En este paso la promesa se vuelve una revisión de verdad.

`unknown` es lo contrario de `any`. Con `any`, TypeScript deja hacer todo; con `unknown`, no deja hacer **nada** hasta que revises qué es. Agrega:

```ts
function esAviso(x: unknown): x is Aviso {
  return typeof x === 'object' && x !== null
    && 'id' in x && typeof x.id === 'number'
    && 'titulo' in x && typeof x.titulo === 'string'
    && 'contenido' in x && typeof x.contenido === 'string'
    && 'creado' in x && typeof x.creado === 'string';
}

function avisosDe(cuerpo: unknown): Aviso[] {
  if (typeof cuerpo !== 'object' || cuerpo === null || !('data' in cuerpo) || !Array.isArray(cuerpo.data)) {
    throw new Error('La respuesta no trae el sobre "data"');
  }
  const todos: unknown[] = cuerpo.data;
  const buenos = todos.filter(esAviso);
  if (buenos.length < todos.length) {
    console.log(`  descartados: ${todos.length - buenos.length} de ${todos.length}`);
  }
  return buenos;
}

const buena: unknown = JSON.parse('{"data":[{"id":1,"titulo":"Simulacro","contenido":"A las 11","publicado":true,"creado":"2026-09-10T10:00:00-06:00"}]}');
const rota: unknown = JSON.parse('{"data":[{"id":"1","titulo":"Simulacro","contenido":"A las 11","publicado":true,"creado":"2026-09-10T10:00:00-06:00"}]}');

console.log('Buena:', avisosDe(buena).length);
console.log('Rota:', avisosDe(rota).length);

async function deTuApi(): Promise<void> {
  const respuesta = await fetch('http://127.0.0.1:8000/api/avisos');
  const cuerpo: unknown = await respuesta.json();
  console.log('Tu API:', avisosDe(cuerpo).length, 'avisos con la forma de Aviso');
}

deTuApi();
```

- `x is Aviso` es un **predicado de tipo**. La función devuelve `true` o `false`, y cuando devuelve `true`, TypeScript trata a `x` como un `Aviso`. Por eso `todos.filter(esAviso)` sale como `Aviso[]` sin ningún `as`.
- `'id' in x` le confirma a TypeScript que el campo existe, y el `typeof` que sigue revisa su tipo. Es la revisión que no puede hacer `instanceof Aviso`, porque la interfaz no existe al correr.
- Se revisan los campos que tu pantalla usa. `categoria` y `autor` son opcionales en la interfaz, así que no se exigen.
- `rota` trae el `id` como texto (`"1"`): lo que pasa cuando alguien cambia la API sin avisar. El guardián lo deja fuera en vez de dejarlo entrar a tu pantalla.

Con `composer run dev` en la otra terminal, compila y corre:

```bash
npx tsc -p practica-ts && node practica-ts/salida/frontera.js
```

```
Buena: 1
  descartados: 1 de 1
Rota: 0
Tu API: 7 avisos con la forma de Aviso
```

El número de la última línea depende de cuántos avisos publicados tenga tu base. Si tu API cumple con la forma, no se descarta ninguno.

**Rómpelo, y regrésalo:**

- Agrega `console.log(buena.data);` al final. El compilador responde `error TS18046: 'buena' is of type 'unknown'.`: primero se revisa, después se usa.
- Agrega `const crudo: any = rota;` y `console.log(crudo.data[0].id.toFixed(0));`. Compila sin errores, y al correr: `TypeError: crudo.data[0].id.toFixed is not a function`. Es la diferencia entre `unknown` y `any`, en dos líneas.

**En Angular** se escribe igual: el servicio pide la respuesta como `unknown` y la pasa por el guardián.

```ts
listar(): Observable<Aviso[]> {
  return this.http.get<unknown>('/api/avisos').pipe(map(avisosDe));
}
```

### Paso 9 · En qué va la petición: una unión discriminada

Tu lista de avisos guarda su estado en tres propiedades: `avisos`, `cargando` y `error`. Nada impide que queden en una combinación que no tiene sentido, como `cargando` en `true` con un error ya escrito. Una **unión discriminada** describe cada estado posible y lo que trae cada uno. Agrega:

```ts
type Errores = Record<string, string[]>;

type Estado =
  | { tipo: 'cargando' }
  | { tipo: 'listo'; avisos: Aviso[] }
  | { tipo: 'invalido'; errores: Errores }
  | { tipo: 'fallo'; codigo: number };

function mensaje(estado: Estado): string {
  switch (estado.tipo) {
    case 'cargando':
      return 'Cargando avisos...';
    case 'listo':
      return `${estado.avisos.length} avisos`;
    case 'invalido':
      return Object.values(estado.errores).flat().join(' ');
    case 'fallo':
      return `Tu API respondió ${estado.codigo}`;
    default: {
      const olvidado: never = estado;
      return olvidado;
    }
  }
}

const estados: Estado[] = [
  { tipo: 'cargando' },
  { tipo: 'listo', avisos: avisosDe(buena) },
  { tipo: 'invalido', errores: { titulo: ['The titulo field is required.'], contenido: ['The contenido field is required.'] } },
  { tipo: 'fallo', codigo: 403 }
];

for (const estado of estados) {
  console.log(`[${estado.tipo}]`, mensaje(estado));
}
```

- `tipo` es el **discriminante**: dentro de `case 'listo'`, TypeScript sabe que el estado trae `avisos`; dentro de `case 'invalido'`, que trae `errores`.
- `Errores` es la forma exacta del `errors` de un 422 de Laravel: por cada campo, una lista de mensajes. Es el tipo de la propiedad `errores` de tu formulario reactivo.
- El `default` con `never` es un guardia. Si todos los casos están cubiertos, ahí no puede llegar nada, y `never` es el tipo de "nada".

**Rómpelo, y regrésalo:**

- Agrega un quinto estado a la unión, `{ tipo: 'sin-sesion' }`. El compilador responde en la línea del `default`: `error TS2322: Type '{ tipo: "sin-sesion"; }' is not assignable to type 'never'.`
- En `case 'listo'`, cambia `estado.avisos.length` por `estado.errores`. El compilador responde `error TS2339: Property 'errores' does not exist on type '{ tipo: "listo"; avisos: Aviso[]; }'.`

El primero es el que vale la pena recordar: al agregar un estado nuevo, **el compilador te lleva a cada `switch` que no lo atiende**. En PHP, a un `match` al que le falta un caso le pasa lo mismo, pero al correr: lanza `UnhandledMatchError` cuando ese valor llega, con el sistema funcionando.

### Paso 10 · Los tipos de lo que mandas, derivados de `Aviso`

Tu app de Angular tiene `NuevoAviso` escrita a mano junto a `Aviso`. Si mañana un campo cambia de nombre, hay que acordarse de cambiarlo en las dos. TypeScript puede **derivar** un tipo de otro. Agrega:

```ts
type NuevoAviso = Pick<Aviso, 'titulo' | 'contenido'> & { categoria_id: number };
type Renglon = Pick<Aviso, 'id' | 'titulo'>;
type Borrador = Partial<NuevoAviso>;

const paraCrear: NuevoAviso = { titulo: 'Simulacro de sismo', contenido: 'A las 11, en el patio', categoria_id: 1 };
const aMedias: Borrador = { titulo: 'Simulacro de sismo' };
const renglones: Renglon[] = avisosDe(buena).map(({ id, titulo }) => ({ id, titulo }));

console.log('Para el POST:', paraCrear);
console.log('A medias:', aMedias);
console.log('Renglones:', renglones);
```

| Herramienta | Qué hace | Aquí |
|---|---|---|
| `Pick<T, K>` | se queda solo con los campos `K` de `T` | lo que viaja en el POST; los renglones de una lista corta |
| `Omit<T, K>` | quita los campos `K` de `T` | lo mismo que `Pick`, dicho al revés |
| `Partial<T>` | vuelve opcionales todos los campos | un formulario a medio llenar |
| `A & B` | junta los campos de dos tipos | lo de `Aviso`, más `categoria_id`, que `Aviso` no tiene |

Corre el archivo completo:

```bash
npx tsc -p practica-ts && node practica-ts/salida/frontera.js
```

```
Buena: 1
  descartados: 1 de 1
Rota: 0
[cargando] Cargando avisos...
[listo] 1 avisos
[invalido] The titulo field is required. The contenido field is required.
[fallo] Tu API respondió 403
Para el POST: {
  titulo: 'Simulacro de sismo',
  contenido: 'A las 11, en el patio',
  categoria_id: 1
}
A medias: { titulo: 'Simulacro de sismo' }
Renglones: [ { id: 1, titulo: 'Simulacro' } ]
Tu API: 7 avisos con la forma de Aviso
```

`Tu API` sale al último aunque su función está escrita antes que los pasos 9 y 10: la respuesta llega después, como en el paso 6.

**Rómpelo, en `paraCrear`, y regrésalo:**

- Agrega `id: 9`. El compilador responde: `Object literal may only specify known properties, and 'id' does not exist in type 'NuevoAviso'.` Es la idea de la asignación masiva de la sesión 5, del lado de la pantalla: allá `$fillable` decide qué campos se pueden escribir desde fuera; aquí, el tipo decide qué campos se pueden mandar.
- Quita `contenido`. El compilador responde: `Property 'contenido' is missing in type '{ titulo: string; categoria_id: number; }' but required in type 'Pick<Aviso, "titulo" | "contenido">'.`
- Escribe `categoria_id: '1'`. El compilador responde: `error TS2322: Type 'string' is not assignable to type 'number'.`

### Checkpoint de la parte C

- `npx tsc -p practica-ts` sigue **sin errores**, con los tres archivos en la carpeta.
- `node practica-ts/salida/frontera.js` imprime lo de arriba, con la línea de **tu API** al final.
- En tu PR va también `practica-ts/frontera.ts`.

---

## Si algo falla

| Lo que ves | Qué pasó |
|---|---|
| `npx tsc` tarda y pregunta si instalar un paquete | No estás dentro de `frontend/`, o no corriste `bash .devcontainer/preparar-angular.sh` |
| `Cannot find module '.../salida/avisos.js'` | Todavía no compilaste, o la compilación tuvo errores: con `&&` no se corre |
| `TypeError: fetch failed` y `connect ECONNREFUSED 127.0.0.1:8000` | Tu Laravel no está corriendo: `composer run dev` en la terminal 1 |
| `error TS2451: Cannot redeclare block-scoped variable` | Copiaste `avisos.ts` a otro `.ts` en la misma carpeta: los dos declaran lo mismo. Borra la copia o cámbiale la extensión |
| Los errores del paso 3 no aparecen | Compilaste otro archivo, o sin `-p practica-ts`: siempre `npx tsc -p practica-ts` |
| El decorador marca errores raros | Compilaste `decorador.ts` suelto (`npx tsc practica-ts/decorador.ts`), sin el `tsconfig.json` que activa los decoradores de Angular. Usa `-p practica-ts` |
| Después de crear `frontera.ts`, `avisos.ts` marca `error TS2739: ... is missing the following properties from type 'Aviso': contenido, publicado` | Falta el `export {};` al principio de `frontera.ts`. Sin él, sus nombres se mezclan con los de `avisos.ts` y las dos interfaces `Aviso` se juntan en una |
