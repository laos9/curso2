<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnviarAvisoPorCorreo implements ShouldQueue
{
    use Queueable;

    public function __construct(public Post $post) {}

    public function handle(): void
    {
        sleep(3);   // aqui iria el envio real a cada usuario
        Log::info('Aviso enviado por correo: '.$this->post->titulo);
    }
}

