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
