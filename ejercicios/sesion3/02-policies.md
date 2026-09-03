# Ejercicio 2 · Policies: quién puede editar qué (por niveles)

Ya tienes sesión: el blog sabe **quién eres**. Falta lo importante: hoy cualquier persona con cuenta puede editar y borrar **los avisos de los demás**. Una **Policy** es la clase donde vive esa decisión, y su gran ventaja es que se escribe UNA vez y la respetan el controlador, las vistas y cualquier otro lugar que pregunte.

> Nivel 1 es la meta de todos hoy. Nivel 2 es la meta de la semana. Nivel 3 es extra.

## Nivel 1 · Cada quien edita lo suyo

### Paso 1 · Los avisos necesitan dueño (5 min)

1. Una migración para guardar quién escribió cada aviso:
   ```bash
   php artisan make:migration add_user_id_to_posts_table
   ```
   ```php
   public function up(): void
   {
       Schema::table('posts', function (Blueprint $table) {
           $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
       });
   }

   public function down(): void
   {
       Schema::table('posts', function (Blueprint $table) {
           $table->dropForeign(['user_id']);
           $table->dropColumn('user_id');
       });
   }
   ```
   ```bash
   php artisan migrate --seed
   ```
   > Va `nullable` a propósito: los avisos que ya existían no tenían dueño. Al sembrar, el `UserSeeder` se los asigna al admin.

2. En `app/Models/Post.php`: suma `'user_id'` al `$fillable` y agrega la relación.
   ```php
   public function user()
   {
       return $this->belongsTo(User::class);
   }
   ```

3. En `PostController@store`, el aviso nace con dueño:
   ```php
   $datos['user_id'] = auth()->id();

   Post::create($datos);
   ```

   ✅ **Checkpoint 1:** creas un aviso entrando como editor y en Tinker `Post::latest()->first()->user->name` responde "Editor de guardia".

### Paso 2 · Nace la Policy (8 min)

4. Genera la clase:
   ```bash
   php artisan make:policy PostPolicy --model=Post
   ```
   Laravel la encuentra **solo**, por convención: `App\Models\Post` busca `App\Policies\PostPolicy`. No hay que registrarla en ningún lado.

5. En `app/Policies/PostPolicy.php` deja estos dos métodos (el resto los puedes borrar por ahora):
   ```php
   public function update(User $user, Post $post): bool
   {
       return $user->id === $post->user_id;
   }

   public function delete(User $user, Post $post): bool
   {
       return $user->id === $post->user_id;
   }
   ```
   Léelo en voz alta: "puede actualizar si es el dueño". Esa frase es toda la Policy.

### Paso 3 · Que alguien pregunte (5 min)

6. En `PostController`, antes de editar o borrar:
   ```php
   use Illuminate\Support\Facades\Gate;

   public function edit(Post $post)
   {
       Gate::authorize('update', $post);
       // ...lo que ya tenías
   }

   public function update(Request $request, Post $post)
   {
       Gate::authorize('update', $post);
       // ...
   }

   public function destroy(Post $post)
   {
       Gate::authorize('delete', $post);
       // ...
   }
   ```
   Si la Policy dice que no, Laravel corta con un **403 Prohibido** y jamás entra a tu código.

   > **La otra forma, la que vas a ver en proyectos existentes:** `$this->authorize('update', $post)`. Es la misma llamada, pero desde el controlador; en Laravel 11 y 12 el controlador base viene vacío, así que primero hay que sumarle el trait: `use Illuminate\Foundation\Auth\Access\AuthorizesRequests;` en `app/Http/Controllers/Controller.php`. En versiones anteriores venía de fábrica, por eso lo encuentras sin trait en código más viejo.

7. Y que la vista no ofrezca lo que no se puede. En `<x-tarjeta-post>`, envuelve los botones:
   ```blade
   @can('update', $post)
       <a href="{{ route('avisos.edit', $post) }}" class="text-blue-700 text-sm font-semibold hover:underline">Editar</a>
   @endcan

   @can('delete', $post)
       <form method="POST" action="{{ route('avisos.destroy', $post) }}" class="inline"
             onsubmit="return confirm('¿Borrar este aviso?')">
           @csrf
           @method('DELETE')
           <button class="text-red-600 text-sm font-semibold hover:underline">Borrar</button>
       </form>
   @endcan
   ```
   **Ojo con la trampa clásica:** esconder el botón NO es proteger. Cualquiera puede mandar el formulario a mano. El botón escondido es cortesía visual; la seguridad es el `Gate::authorize` del paso 6. Siempre los dos.

   ✅ **Checkpoint nivel 1:** entra como editor, crea un aviso: ves Editar y Borrar en el tuyo, y NO en los demás. Prueba la puerta trasera: copia la URL de editar de un aviso ajeno y pégala en el navegador. Debe responder **403**.

## Nivel 2 · El admin puede con todo

8. Un aviso institucional a veces lo tiene que corregir el administrador. En la Policy, un método especial que corre ANTES que los demás:
   ```php
   public function before(User $user, string $ability): ?bool
   {
       if ($user->rol === 'admin') {
           return true;
       }

       return null;
   }
   ```
   Los tres valores importan: `true` autoriza sin preguntar más, `false` prohíbe de tajo, y **`null` significa "no opino, que decidan los demás métodos"**. Ese `null` es la pieza que la gente olvida y por eso su admin rompe todo lo demás.

   ✅ **Checkpoint nivel 2:** como `admin@blog.test` ves Editar y Borrar en TODOS los avisos; como editor, solo en los tuyos.

## Nivel 3 · Extra

9. **Crear también se autoriza.** Agrega a la Policy:
   ```php
   public function create(User $user): bool
   {
       return in_array($user->rol, ['admin', 'editor']);
   }
   ```
   y en el controlador, en `create` y `store`: `Gate::authorize('create', Post::class);` (aquí va la clase, no un objeto: todavía no existe el aviso).

10. **El candado desde la ruta.** Laravel puede autorizar sin que el controlador se entere:
    ```php
    Route::put('/avisos/{post}', [PostController::class, 'update'])
        ->name('avisos.update')
        ->can('update', 'post');
    ```

11. **Una Gate suelta.** Para permisos que no giran alrededor de un modelo (por ejemplo, ver un panel de estadísticas), en `AppServiceProvider@boot`:
    ```php
    Gate::define('ver-panel', fn ($user) => $user->rol === 'admin');
    ```
    y en la vista: `@can('ver-panel') ... @endcan`. **La regla para elegir:** si la pregunta es sobre UN registro concreto, es Policy; si es un permiso general, es Gate.

## Problemas comunes

| Síntoma | Causa probable |
|---|---|
| `This action is unauthorized` con TODO, hasta con lo tuyo | Los avisos viejos no tienen `user_id`, o creaste el aviso antes del paso 3. Vuelve a sembrar o crea uno nuevo |
| El botón sigue apareciendo en avisos ajenos | El `@can` quedó fuera del `@foreach`, o le pasaste el modelo equivocado |
| `Call to undefined method ...::authorize()` | Usaste `$this->authorize()` sin el trait: usa `Gate::authorize()` o suma el trait al controlador base (paso 6) |
| La Policy no se aplica nunca (todo pasa) | El nombre no sigue la convención: el modelo `Post` busca `PostPolicy` en `app/Policies/` |
| El admin no puede con nada | Tu `before()` regresa `false` en vez de `null` cuando no es admin |
| `Undefined property: rol` | Falta la migración de la columna `rol` que llegó con el pull, o no corriste `php artisan migrate` |
