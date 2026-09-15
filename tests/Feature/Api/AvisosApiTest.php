<?php

use App\Jobs\EnviarAvisoPorCorreo;
use App\Models\Categoria;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    $this->categoria = Categoria::factory()->create(['nombre' => 'Avisos generales']);
    $this->editor = User::factory()->create(['rol' => 'editor']);
});

describe('leer avisos, sin token', function () {

    test('el listado responde 200 y trae solo los publicados', function () {
        Post::factory()->count(3)->create(['categoria_id' => $this->categoria->id, 'publicado' => true]);
        Post::factory()->create(['categoria_id' => $this->categoria->id, 'publicado' => false]);

        $this->getJson('/api/avisos')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('cada aviso trae los campos que decidio el PostResource', function () {
        Post::factory()->create(['categoria_id' => $this->categoria->id, 'titulo' => 'Cambio de horario']);

        $this->getJson('/api/avisos')
            ->assertJsonPath('data.0.titulo', 'Cambio de horario')
            ->assertJsonPath('data.0.categoria.nombre', 'Avisos generales');
    });
});

describe('escribir avisos', function () {

    test('sin token responde 401', function () {
        $this->postJson('/api/avisos', ['titulo' => 'x'])->assertStatus(401);
    });

    test('con token crea el aviso y responde 201', function () {
        Sanctum::actingAs($this->editor);

        $this->postJson('/api/avisos', [
            'titulo' => 'Capacitacion el viernes',
            'contenido' => 'A las 9 en la sala 2.',
            'categoria_id' => $this->categoria->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('posts', [
            'titulo' => 'Capacitacion el viernes',
            'user_id' => $this->editor->id,
        ]);

        Queue::assertPushed(EnviarAvisoPorCorreo::class,
            fn ($trabajo) => $trabajo->post->titulo === 'Capacitacion el viernes');
    });

    test('sin titulo responde 422 y dice cual campo fallo', function () {
        Sanctum::actingAs($this->editor);

        $this->postJson('/api/avisos', ['contenido' => 'sin titulo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['titulo', 'categoria_id']);
    });
});

describe('la Policy de la sesion 3, ahora en la API', function () {

    test('editar un aviso ajeno responde 403', function () {
        $ajeno = Post::factory()->create([
            'categoria_id' => $this->categoria->id,
            'user_id' => User::factory()->create(['rol' => 'editor'])->id,
        ]);

        Sanctum::actingAs($this->editor);

        $this->putJson("/api/avisos/{$ajeno->id}", [
            'titulo' => 'Secuestrado',
            'contenido' => 'mio ahora',
            'categoria_id' => $this->categoria->id,
        ])->assertStatus(403);

        expect($ajeno->fresh()->titulo)->not->toBe('Secuestrado');
    });

    test('editar el propio responde 200', function () {
        $mio = Post::factory()->create([
            'categoria_id' => $this->categoria->id,
            'user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $this->putJson("/api/avisos/{$mio->id}", [
            'titulo' => 'Corregido',
            'contenido' => 'ya quedo',
            'categoria_id' => $this->categoria->id,
        ])->assertStatus(200)->assertJsonPath('data.titulo', 'Corregido');
    });

    test('un admin si puede con el ajeno', function () {
        $ajeno = Post::factory()->create(['categoria_id' => $this->categoria->id]);
        Sanctum::actingAs(User::factory()->create(['rol' => 'admin']));

        $this->deleteJson("/api/avisos/{$ajeno->id}")->assertStatus(204);
        $this->assertDatabaseMissing('posts', ['id' => $ajeno->id]);
    });
});

describe('tokens', function () {

    test('credenciales correctas devuelven un token', function () {
        $this->postJson('/api/token', [
            'email' => $this->editor->email,
            'password' => 'password',
            'dispositivo' => 'pruebas',
        ])->assertStatus(200)->assertJsonStructure(['token', 'usuario', 'rol']);

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'pruebas']);
    });

    test('credenciales incorrectas responden 422 y no dejan token', function () {
        $this->postJson('/api/token', [
            'email' => $this->editor->email,
            'password' => 'la-que-no-es',
            'dispositivo' => 'pruebas',
        ])->assertStatus(422);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });
});

test('GET /api/yo no publica la tabla users', function () {
    Sanctum::actingAs($this->editor);

    $respuesta = $this->getJson('/api/yo')->assertStatus(200);

    $respuesta->assertJsonPath('nombre', $this->editor->name);
    expect(array_keys($respuesta->json()))->toBe(['id', 'nombre', 'rol']);
});

test('el filtro categoria devuelve solo los avisos de esa categoria', function () {
    $otraCategoria = Categoria::factory()->create(['nombre' => 'Deportes']);

    Post::factory()->count(2)->create(['categoria_id' => $this->categoria->id, 'publicado' => true]);
    Post::factory()->create(['categoria_id' => $otraCategoria->id, 'publicado' => true]);

    $this->getJson("/api/avisos?categoria={$otraCategoria->id}")
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.categoria.nombre', 'Deportes');
});
