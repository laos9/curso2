<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Aviso del blog. Version de nivelacion de la sesion 4: es la misma que
 * construiste en las sesiones 2 y 3, sin las etiquetas del nivel avanzado.
 */
class Post extends Model
{
    protected $fillable = ['titulo', 'contenido', 'categoria_id', 'publicado', 'user_id'];

    protected $casts = [
        'publicado' => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublicados($query)
    {
        return $query->where('publicado', true);
    }
}
