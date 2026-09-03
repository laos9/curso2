<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etiqueta extends Model
{
    public function posts()
{
    return $this->belongsToMany(Post::class);
}

}
