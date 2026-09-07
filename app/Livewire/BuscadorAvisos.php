<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Post;

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
