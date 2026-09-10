<?php

// Solucion de la sesion 5: app/Http/Resources/PostResource.php
//   php artisan make:resource PostResource
//
// Es el `tarjeta-post.blade.php` de la API. El componente Blade decide que ve
// el navegador; este archivo decide que ve otra aplicacion. Mismo modelo, dos
// presentaciones. Aqui es donde se queda fuera lo que no debe salir.
//
// OJO con el nombre: ya tienes app/Filament/Resources/Posts/PostResource.php,
// que es un Resource de Filament. Son dos cosas distintas en carpetas distintas.

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'resumen' => $this->resumen,
            'contenido' => $this->contenido,
            'publicado' => $this->publicado,

            // whenLoaded solo incluye la relacion si el controlador la cargo con
            // with(). Sin esto, pintar la categoria de cada aviso dispararia una
            // consulta por fila: el N+1 de la sesion 2, ahora en la API.
            'categoria' => $this->whenLoaded('categoria', fn () => [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
            ]),
            'autor' => $this->whenLoaded('user', fn () => $this->user?->name),

            // Fecha en formato estandar, no en el formato de tu pantalla:
            // quien consume la API decide como mostrarla.
            'creado' => $this->created_at->toIso8601String(),
        ];
    }
}
