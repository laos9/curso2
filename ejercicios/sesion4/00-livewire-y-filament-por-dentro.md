# Lectura · Livewire y Filament, por dentro

> Es la versión escrita del bloque conceptual de la sesión 4. Sirve para leerla antes de la clase, para volver a ella después, y como referencia mientras haces la tarea. Las guías de práctica son `01-filament-resource.md` y `02-policies-en-filament.md`; esta lectura explica **qué** es lo que vas a usar y **por qué** funciona así.

## Antes de empezar: los tres comandos

Filament exige dos extensiones de PHP que el contenedor del curso no trae, y para generar un panel necesita que tu blog tenga los modelos `Post` y `Categoria`. Por eso la sesión arranca con tres comandos, en este orden:

```bash
bash .devcontainer/preparar-filament.sh
bash .devcontainer/nivelar-blog.sh
composer require filament/filament:"^5.0"
```

- **`preparar-filament.sh`** compila `intl` y `zip` dentro del contenedor. Alrededor de un minuto la primera vez; la segunda vez dice "ya están instaladas" y termina.
- **`nivelar-blog.sh`** crea **solo lo que te falte**: los modelos, las tablas con las columnas `publicado` y `user_id`, y los datos de práctica. Nunca toca un archivo que ya exista: si tu blog está completo, dice "ya existe" en todo. Si te avisa que tu `Post.php` no tiene `publicado` o `user_id` en `$fillable`, agrégalos: sin eso Filament mostrará esos campos pero no los guardará.
- **`composer require`** descarga Filament y sus dependencias. De 2 a 5 minutos según la red. Se lanza y se deja correr.

Los dos primeros llegan a tu proyecto con el ritual de inicio (`git merge upstream/main -X theirs`). Si `composer require` termina con "requires ext-intl" o "ext-zip", el primer script no corrió: córrelo y repite.

## 1. Tres formas de que una página reaccione a un clic

Cuando alguien da clic en "Filtrar", hay tres maneras de que la página responda.

**Páginas servidas.** Cada clic pide la página entera y el servidor la pinta completa. Es tu blog público de las sesiones 2 y 3: simple, y la opción correcta para páginas que se leen más de lo que se tocan. Su costo: un filtro es un viaje completo y la pantalla parpadea.

**Una aplicación en JavaScript** (Vue, React). El navegador se vuelve una aplicación aparte: pide JSON a una API, guarda estado, pinta. Mucha interactividad a cambio de una segunda aplicación, otro lenguaje y una API en medio: la lógica vive en dos lugares.

**Livewire** (2019, Caleb Porzio). La lógica **se queda en PHP**. El navegador manda qué pasó y recibe solo el fragmento de HTML que cambió. Una sola aplicación, sin escribir JavaScript. Livewire es la base del stack **TALL** (Tailwind, Alpine, Laravel, Livewire), y Filament está construido encima.

## 2. Reactividad: la pantalla es una función del estado

Sin reactividad, tú sincronizas la pantalla con los datos, cada vez:

```js
boton.addEventListener('click', async () => {
    const r = await fetch('/api/contador', { method: 'POST' });
    const { total } = await r.json();
    document.getElementById('total').textContent = total;   // tú
    if (total > 9) etiqueta.classList.add('rojo');            // tú
});
```

Con reactividad no escribes "cambia este texto": **describes cómo se ve la pantalla para cada estado**, y cuando el estado cambia, la pantalla se recalcula sola.

```php
public int $contador = 0;                 // ESTADO

public function incrementar(): void       // ACCIÓN: cambia el estado
{
    $this->contador++;
}
```

```blade
<button wire:click="incrementar">+1</button>
<span>{{ $contador }}</span>
@if ($contador > 9) <span class="rojo">límite</span> @endif
```

Nadie escribió "pon la etiqueta límite": la vista dice que existe cuando el contador pasa de 9, y aparece sola. Es la misma idea que hace funcionar a Vue y a React. La diferencia de Livewire es **dónde vive el estado**: en una propiedad de PHP, no en el navegador.

## 3. Un componente Livewire: una clase con estado y una vista

```bash
php artisan make:livewire BuscadorAvisos --class
```

> El `--class` importa: sin él, Livewire 4 crea un componente de **un solo archivo** (`resources/views/components/⚡buscador-avisos.blade.php`, clase y vista juntas). Con `--class` crea las dos piezas separadas, que es la forma de esta lectura y la que usa Filament por dentro.

Crea dos archivos.

```php
// app/Livewire/BuscadorAvisos.php
class BuscadorAvisos extends Component
{
    public string $busqueda = '';        // ESTADO: viaja en cada petición

    public function limpiar(): void      // ACCIÓN: se llama desde el HTML
    {
        $this->busqueda = '';
    }

    public function render()             // LA VISTA: se vuelve a pintar
    {
        return view('livewire.buscador-avisos', [
            'avisos' => Post::where('titulo', 'like', "%{$this->busqueda}%")->get(),
        ]);
    }
}
```

```blade
{{-- resources/views/livewire/buscador-avisos.blade.php --}}
<div>
    <input wire:model.live="busqueda" placeholder="Buscar aviso">
    <button wire:click="limpiar">Limpiar</button>
    @foreach ($avisos as $aviso)
        <p>{{ $aviso->titulo }}</p>
    @endforeach
</div>
```

- **Propiedades públicas = estado.** Lo que pongas ahí viaja entre navegador y servidor en cada petición.
- **Métodos públicos = acciones.** Se llaman desde el HTML con `wire:click`.
- **`render()` = la vista.** Se vuelve a pintar después de cada acción o cambio.
- **`wire:` es el puente.** `wire:model.live` manda cada cambio del input al servidor; `wire:click` llama a un método. No hay JavaScript tuyo en ningún lado.
- La vista necesita **un solo elemento raíz**: es lo que Livewire vigila. Se usa en cualquier página Blade con `<livewire:buscador-avisos />`.

Compáralo con el buscador de la sesión 2: ahí necesitabas una ruta, un método en el controlador y un formulario con botón "Buscar". Aquí el método sigue existiendo (es `render()` con la consulta dentro); la ruta la pone Livewire (`/livewire/update`), y el formulario con botón desaparece porque `wire:model.live` manda cada cambio.

## 4. Cada tecla es una petición: el viaje por dentro

1. **Render inicial.** El servidor pinta el componente y mete el estado serializado dentro del HTML, en un atributo `wire:snapshot`. El servidor no guarda nada entre peticiones.
2. **Evento `wire:*`.** Livewire JS arma una petición con el snapshot, los cambios y las llamadas a métodos, y la manda a `POST /livewire/update`.
3. **Servidor.** Recrea el componente desde el snapshot, aplica los cambios, corre el método y vuelve a ejecutar `render()`.
4. **Respuesta.** HTML nuevo más un snapshot nuevo, **firmado** con un `checksum`: si alguien edita el estado a mano en el navegador, el servidor lo rechaza.
5. **Morph.** Livewire compara el HTML nuevo con el que había y toca solo los nodos que cambiaron. El foco y el scroll se conservan.

Así se ve la petición, resumida:

```json
POST /livewire/update
{
  "snapshot": {
    "state": { "busqueda": "cur" },
    "memo":  { "name": "buscador-avisos", "id": "x7k2" },
    "checksum": "b1e7..."
  },
  "updates": { "busqueda": "curs" },
  "calls": []
}
```

Y la respuesta trae `{ "html": "<div wire:snapshot=...>...</div>", "snapshot": {...} }`.

**Compruébalo tú:** herramientas del navegador, pestaña Red, y escribe en la búsqueda de tu tabla de Filament. Una petición por cambio, y la respuesta es HTML, no JSON.

## 5. Las directivas `wire:` en un formulario que se usa

```php
use Livewire\Attributes\Validate;

class FormularioAviso extends Component
{
    #[Validate('required|min:3')]
    public string $titulo = '';

    #[Validate('required')]
    public string $contenido = '';

    public function guardar(): void
    {
        $datos = $this->validate();          // aplica los #[Validate]
        Post::create($datos + ['user_id' => auth()->id()]);
        $this->dispatch('aviso-guardado');
    }
}
```

```blade
<form wire:submit="guardar">
    <input wire:model.live.debounce.300ms="titulo">
    @error('titulo') <span>{{ $message }}</span> @enderror
    <textarea wire:model="contenido"></textarea>
    <input wire:model.blur="resumen">
    <button wire:loading.attr="disabled">
        <span wire:loading.remove>Guardar</span>
        <span wire:loading>Guardando...</span>
    </button>
</form>
```

Tres ritmos para el mismo enlace:

| Directiva | Cuándo viaja al servidor |
|---|---|
| `wire:model.live` | en cada cambio (con `.debounce.300ms`, espera a que dejes de escribir) |
| `wire:model` (sin modificador) | **diferido**: viaja con la siguiente acción |
| `wire:model.blur` | al salir del campo |

- **`#[Validate]`** se aplica en cada actualización que llega. Con `.live`, el error del título aparece **mientras escribes**, sin botón.
- **`wire:submit="guardar"`** llama a la acción y manda lo diferido junto.
- **`wire:loading`** muestra u oculta cosas mientras un viaje está en curso; `wire:loading.attr="disabled"` deshabilita el botón para evitar el doble clic; `wire:target="guardar"` limita el indicador a una acción.

## 6. El ciclo de vida de un componente

| Momento | Qué pasa |
|---|---|
| `mount()` | una sola vez, cuando llega la página; recibe parámetros |
| `render()` | pinta la vista con el estado inicial |
| hydrate | **cada viaje**: recrea el componente desde el snapshot |
| `updated...()` | hooks que corren cuando una propiedad cambió |
| acción + `render()` | tu método, y la vista otra vez |
| dehydrate | snapshot nuevo, listo para el siguiente viaje |

```php
public function mount(Categoria $categoria): void   // una vez
{
    $this->categoriaId = $categoria->id;
}

public function updatedBusqueda(): void            // cada vez que cambia $busqueda
{
    $this->resetPage();                             // vuelve a la página 1 de la tabla
}
```

`mount()` corre una vez, con la carga de la página: ahí va lo que se calcula al inicio. Todo lo demás corre **en cada viaje**; por eso lo caro no va en `render()`, se ejecutaría en cada tecla. `updatedBusqueda()` es el hook de `$busqueda`: Livewire lo llama solo, por el nombre. Cambiaste el texto de búsqueda estando en la página 3: sin ese hook verías la página 3 de los resultados nuevos.

## 7. Componentes que se hablan, y lo que se queda en el navegador

Dos componentes en la misma página no comparten estado. Se avisan con **eventos**:

```php
// en FormularioAviso, al final de guardar():
$this->dispatch('aviso-guardado', id: $post->id);

// en ListaAvisos:
use Livewire\Attributes\On;

#[On('aviso-guardado')]
public function refrescar(int $id): void
{
    // no hace falta nada más: render() vuelve a correr
}
```

Filament los usa para refrescar tablas y mostrar notificaciones.

Lo que **no necesita datos** (abrir un menú, un acordeón, mostrar u ocultar) lo hace **Alpine.js**, que viene incluido con Livewire. Es la misma idea reactiva (estado → vista) pero en el navegador, sin viaje al servidor:

```blade
<div x-data="{ abierto: false }">
    <button @click="abierto = !abierto">Filtros</button>
    <div x-show="abierto">...</div>
</div>
```

Filament pinta menús, modales y desplegables con Alpine, y todo lo que toca datos con Livewire.

## 8. Cuándo Livewire sí, y cuándo no

**Sí:** formularios con validación en vivo, tablas con búsqueda, filtros, orden y paginación, modales, asistentes por pasos, dashboards. Paneles administrativos: todo lo anterior junto.

**No:** editores gráficos, mapas con arrastre continuo, juegos, aplicaciones que deben funcionar sin conexión, cualquier cosa con decenas de interacciones por segundo.

La regla: **cada interacción es un viaje al servidor**. En local son decenas de milisegundos; en producción, cientos. Si la experiencia depende de que algo pase en menos de eso, Livewire no es la herramienta. En los sistemas reales la administración completa vive en Livewire a través de Filament, y lo público sigue siendo Blade con Tailwind, como tu blog. Las dos cosas conviven en el mismo proyecto.

## 9. Filament: qué problema resuelve

Todo sistema tiene dos caras. La **pública** es la que diseñas: tu portada, tu tarjeta de aviso, tu Tailwind; entra cualquier visitante. La de **administración** es donde se dan de alta, se corrigen y se borran los registros de cada tabla, con búsqueda, filtros, permisos y reportes. Esa segunda cara es casi igual en todos los sistemas: una lista con búsqueda, un formulario con validación, Editar y Borrar, un login y roles. Lo único que cambia es el modelo.

La cuenta que importa: tu CRUD de avisos de las sesiones 2 y 3 son alrededor de 200 líneas para un solo modelo (controlador, dos vistas, validación, rutas, Policy, botones). Un sistema real tiene más de 100 modelos con esa misma cara. Son 20 000 líneas de lo mismo, escritas y mantenidas a mano; cada cambio de diseño se repite 100 veces.

Filament es un conjunto de paquetes para Laravel, **escritos con Livewire**, que traen hecha esa cara: formularios (Schemas), tablas, acciones, notificaciones, widgets, y el **Panel Builder**, que las junta en una aplicación completa con login y menú. Es gratuito y de código abierto; nació en 2021 (Dan Harrin) y hoy va en la versión 5. No reemplaza nada de lo que ya sabes: usa tus modelos, tus relaciones, tus scopes y tu Policy tal cual. Reemplaza el trabajo repetido.

## 10. ¿Por qué un panel?

Un **panel** es una aplicación aparte, montada en una URL de tu proyecto (`/admin`), con su propio login, su menú, su dashboard y su lista de Resources. Comparado con tu blog público:

| Tu blog público (`/`) | El panel (`/admin`) |
|---|---|
| tus vistas Blade, tu Tailwind, tu diseño | cero vistas tuyas: la interfaz es de Filament, igual en todos los sistemas |
| tus rutas, escritas en `web.php` | rutas que no escribes (`php artisan route:list --path=admin`) |
| cualquier visitante; sesión solo para escribir | solo usuarios con sesión, y adentro solo lo que tu Policy permita |
| Livewire solo donde tú lo pongas (el buscador) | todo es Livewire: cada pantalla es un componente |

Tres razones para tenerlo aparte:

1. **Separación.** Lo público se diseña; la administración no se diseña, **se declara**. Por eso conviven en el mismo proyecto sin estorbarse: tu portada sigue siendo tuya y el panel se ve como cualquier panel de Filament.
2. **Una sola puerta.** El `authMiddleware` del panel y `canAccessPanel()` deciden quién entra. Adentro, tus Policies deciden qué toca cada quien. Dos capas, dos preguntas distintas.
3. **Escala.** Decenas de Resources se ordenan por grupos en el menú, y un mismo proyecto puede tener **varios paneles**, cada uno con su URL, su login y su menú: uno para administración, otro para operación, uno por tipo de usuario. Todos comparten los mismos modelos, las mismas migraciones y las mismas Policies: cambia la puerta, no la casa. En producción es normal ver más de diez paneles en un solo proyecto.

## 11. Livewire y Filament: la misma máquina, con las piezas ya escritas

Todo lo que escribiste en el buscador de la sección 3 tiene su equivalente dentro del panel:

| Lo que escribiste en el buscador | Lo mismo, dentro del panel |
|---|---|
| `public string $busqueda`: estado que viaja en el snapshot | `$data['titulo']` del formulario, la búsqueda, el orden y los filtros de la tabla: estado en el mismo snapshot |
| `wire:model.live.debounce.300ms` | `TextInput::make()->live(debounce: 500)` y la caja de búsqueda de la tabla |
| `public function limpiar()`: una acción | `Action::make('publicar')->action(...)`, `EditAction`, el botón Guardar (`create()` de la Page) |
| `render()` con tu consulta y tu vista | `table()` arma la consulta y `form()` el formulario; la vista la pone Filament |
| `wire:loading` | el spinner del botón y la tabla atenuada mientras carga |
| `$this->dispatch('aviso-guardado')` | `Notification::make()->send()`: viaja en la misma respuesta |
| `mount()` | `EditRecord::mount($record)` carga el registro en `$data` |

Tres cosas que se siguen de la tabla. Cada pantalla del panel **es** un componente Livewire: `ListPosts extends ListRecords`, y hasta arriba de esa cadena está `Livewire\Component`, la misma clase de tu buscador. Cada clic dentro del panel es el mismo `POST /livewire/update` con el mismo snapshot de la sección 4, solo que con más estado adentro. Y por eso Filament no es "otra cosa": es Livewire con las piezas ya escritas, y aplican las mismas reglas: cada interacción viaja al servidor, lo caro no va en `render()`, el estado vive en PHP.

## 12. Filament: el stack TALL, empaquetado para paneles

Filament (2021, Dan Harrin; hoy en su versión 5) es una colección de paquetes sobre Laravel, Livewire, Alpine y Tailwind: **Panel Builder**, **Schemas** (formularios), **Tables**, **Actions**, **Notifications**, **Widgets**. Tablas, formularios, acciones y paneles ya escritos como componentes Livewire. Tú escribes la capa de arriba: una clase que **declara** qué modelo, qué campos y qué columnas. Todo lo demás lo trae hecho.

Las cuatro piezas:

| Pieza | Qué es | Dónde vive |
|---|---|---|
| **Panel** | una zona con su login, su menú y su dashboard, en una URL (`/admin`) | `app/Providers/Filament/AdminPanelProvider.php` |
| **Resource** | el CRUD completo de UN modelo | `app/Filament/Resources/Posts/PostResource.php` |
| **Page** | una pantalla del panel; la lista, el alta y la edición de un Resource son Pages, y **cada una es un componente Livewire** | `Pages/ListPosts.php`, `CreatePost.php`, `EditPost.php` |
| **Widget** | un bloque del dashboard: un contador, una gráfica | `app/Filament/Widgets/` |

Y debajo del Resource, dos archivos que declaran: `Schemas/PostForm.php` (qué campos tiene el formulario) y `Tables/PostsTable.php` (qué columnas, filtros y acciones tiene la tabla). Sus piezas (`TextInput`, `Select`, `TextColumn`, `SelectFilter`, `Action`) vienen con Filament y se configuran encadenando métodos. Por eso son seis archivos y ninguna vista.

`ListPosts extends ListRecords`, y `ListRecords` es un componente Livewire: el buscador de la sección 3 y tu tabla de avisos son **la misma cosa**, con más líneas.

## 13. El panel es una clase

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')                       // la URL: /admin
        ->login()                             // la pantalla de sesión
        ->brandName('Blog de avisos')         // el nombre arriba del menú
        ->colors(['primary' => Color::Amber]) // prueba Color::Blue
        ->discoverResources(
            in: app_path('Filament/Resources'),
            for: 'App\\Filament\\Resources',   // por eso PostResource aparece solo
        )
        ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
        ->pages([Dashboard::class])
        ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
        ->middleware([
            EncryptCookies::class,
            StartSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            // ... los mismos del grupo web de tu blog
        ])
        ->authMiddleware([
            Authenticate::class,              // el candado de la sesión 3
        ]);
}
```

- **`discoverResources`** recorre una carpeta y registra cada Resource que encuentra. Es la razón por la que `PostResource` aparece en el menú sin que lo registres: convención, igual que Laravel encuentra tu `PostPolicy`.
- **`authMiddleware`** es el middleware de la sesión 3, puesto por el panel: sin sesión, redirige a su propio login. **`middleware`** es la misma fila de filtros (cookies, sesión, CSRF) que protege tu blog.
- **Varios paneles = varias clases**, cada una con su URL, su login y su lista de Resources.
- **`/admin` no necesita `npm run dev`.** Filament sirve sus propios assets ya compilados; Vite solo entra si haces un tema propio (`->viteTheme()`).
- En producción, el `authMiddleware` suele sumar un segundo filtro por rol, y el panel trae logo, tema y notificaciones. La estructura es idéntica.

## 14. El viaje de una petición al panel

**Abrir `/admin/posts`:** el middleware del panel (cookies, sesión, CSRF, `Authenticate`) → `ListPosts`, un componente Livewire, se monta con estado vacío → le pide a `PostResource::table()` la definición (columnas, filtros, acciones: solo declaraciones) → con eso arma **una consulta** Eloquent (el `with()` de las relaciones, el `orderBy`, la paginación) → vuelve el HTML de la tabla con el snapshot dentro. A partir de ahí, cada búsqueda o filtro es el viaje corto de Livewire.

**Guardar desde "Editar":** `POST /livewire/update` con el snapshot y los valores del formulario → el mismo middleware → `EditPost` se recrea desde el snapshot → `PostResource::form()` valida (`required`, `maxLength`) → Eloquent hace el `update` (la misma línea que tenía tu controlador) → vuelve HTML nuevo y una notificación; Livewire hace morph y la página no se recarga.

Todo lo que viste en las sesiones 2 y 3 sigue ahí, en el mismo orden. Filament solo puso las piezas.

## 15. Declaras el qué; Filament escribe el cómo

Tu portada de la sesión 2 decía **cómo**:

```php
$avisos = Post::with('categoria')
    ->when($request->q, fn ($q, $texto) => $q->where('titulo', 'like', "%{$texto}%"))
    ->latest()
    ->paginate(10);
```

más un formulario de búsqueda, un `@foreach` y los enlaces de paginación. Tu tabla de hoy dice **qué**:

```php
TextColumn::make('titulo')->searchable()->sortable(),
TextColumn::make('categoria.nombre'),
SelectFilter::make('categoria_id')->relationship('categoria', 'nombre'),
```

Y lo que Filament ejecuta con eso:

```sql
select * from posts where titulo like '%cur%' and categoria_id = 2 order by created_at desc limit 10 offset 0;
select * from categorias where id in (1, 2, 3);
```

Es la misma consulta, con otro autor. La segunda consulta es el `with('categoria')` que aprendiste para evitar el N+1: Filament lo agrega solo cuando ve el punto en `categoria.nombre`.

## 16. Lo que hiciste a mano, en una clase

| Sesiones 2 y 3, a mano | Hoy, con Filament |
|---|---|
| `PostController` con 7 métodos | `PostResource`: qué modelo y con qué icono |
| `crear.blade.php` y `editar.blade.php` | `Schemas/PostForm`: los campos, con su validación dentro |
| `$request->validate([...])` | (vive en cada campo: `required()`, `maxLength()`) |
| rutas en `web.php`, una por método | `Pages/`: tres clases de diez líneas; las rutas salen solas |
| portada con `with('categoria')` y scopes | `Tables/PostsTable`: columnas, filtros y acciones |

Nada de la columna izquierda desaparece del framework: sigue existiendo, pero **lo escribe Filament** a partir de lo que declaras a la derecha. Por eso el orden del curso: quien nunca escribió un `store()` no puede saber qué está pasando cuando el panel guarda. Tú sí.

## 17. Filament también es reactivo

`->live()` es el `wire:model.live` de Filament: cada cambio de ese campo viaja al servidor y el formulario entero se vuelve a pintar.

```php
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

TextInput::make('titulo')
    ->live(debounce: 500)
    ->afterStateUpdated(fn (Set $set, ?string $state) =>
        $set('slug', str($state)->slug()->toString())
    ),
TextInput::make('slug'),

Toggle::make('publicado')
    ->live(),
DateTimePicker::make('publicado_en')
    ->visible(fn (Get $get): bool => (bool) $get('publicado')),
```

`afterStateUpdated` corre en el servidor con el valor nuevo y puede escribir otros campos con `$set`. `visible(fn (Get $get))` se reevalúa en cada render: por eso el campo aparece al activar el interruptor. Sin `->live()` en el interruptor, el campo de fecha no aparecería hasta guardar: el cambio no habría viajado.

## Glosario

- **Estado:** las propiedades públicas de un componente; viajan en cada petición.
- **Snapshot:** el estado serializado (más el nombre y el id del componente) que va y viene dentro del HTML, firmado con un `checksum`.
- **Hydrate / dehydrate:** recrear el componente desde el snapshot al llegar una petición, y volver a serializarlo al responder.
- **Morph:** actualizar en el navegador solo los nodos del HTML que cambiaron.
- **Directiva `wire:`:** atributo de HTML que conecta la vista con el componente (`wire:model`, `wire:click`, `wire:submit`, `wire:loading`).
- **Hook:** método que Livewire llama solo en un momento del ciclo de vida (`mount`, `updatedX`).
- **Evento:** aviso entre componentes (`dispatch` / `#[On]`).
- **Alpine.js:** reactividad en el navegador, sin servidor, para lo que no toca datos.
- **Panel, Resource, Page, Widget:** las cuatro piezas de Filament (sección 9).
- **Schema / Table:** las clases donde declaras el formulario y la tabla de un Resource.
