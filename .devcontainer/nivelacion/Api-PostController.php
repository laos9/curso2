<?php

// Solucion de la sesion 5: app/Http/Controllers/Api/PostController.php
//   php artisan make:controller Api/PostController
//
// Es tu PostController de las sesiones 2 y 3 con dos diferencias: devuelve
// PostResource en vez de view(), y los codigos de estado son explicitos.
// La autorizacion NO cambia: es el mismo Gate::authorize con la misma Policy.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index()
    {
        // paginate() en una API no pinta enlaces: agrega "links" y "meta" al JSON.
        return PostResource::collection(
            Post::publicados()->with(['categoria', 'user'])->latest()->paginate(10)
        );
    }

    public function show(Post $post)
    {
        return new PostResource($post->load(['categoria', 'user']));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Post::class);

        // Si falla, Laravel responde 422 con el detalle por campo. No escribes
        // el mensaje ni la vista: la misma validacion, otra presentacion.
        $datos = $request->validate([
            'titulo' => ['required', 'max:120'],
            'contenido' => ['required'],
            'categoria_id' => ['required', 'exists:categorias,id'],
        ]);

        $datos['user_id'] = $request->user()->id;

        $post = Post::create($datos);

        // 201 es "se creo", no 200. Un cliente serio distingue las dos cosas.
        return (new PostResource($post->load(['categoria', 'user'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Post $post)
    {
        Gate::authorize('update', $post);

        $datos = $request->validate([
            'titulo' => ['required', 'max:120'],
            'contenido' => ['required'],
            'categoria_id' => ['required', 'exists:categorias,id'],
        ]);

        $post->update($datos);

        return new PostResource($post->load(['categoria', 'user']));
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        // 204: salio bien y no hay nada que devolver.
        return response()->noContent();
    }
}
