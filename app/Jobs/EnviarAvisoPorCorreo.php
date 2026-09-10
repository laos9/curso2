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
    throw new \RuntimeException('El servidor de correo no responde');
}

}

