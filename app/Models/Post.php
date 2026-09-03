<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['titulo', 'contenido', 'categoria_id', 'publicado', 'user_id'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    protected $casts = ['publicado' => 'boolean'];

    public function scopePublicados($query)
    {
    return $query->where('publicado', true);
    }

    public function scopeDeCategoria($query, $categoriaId)
    {
    return $query->where('categoria_id', $categoriaId);
    }

    public function etiquetas()
    {
    return $this->belongsToMany(Etiqueta::class);
    }

   public function user()
   {
    return $this->belongsTo(User::class);
   }
}
