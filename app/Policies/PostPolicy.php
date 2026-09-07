<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
 public function before(User $user, string $ability): ?bool
{
    if ($user->rol === 'admin') {
        return true;
    }

    return null;
}
   
public function update(User $user, Post $post): bool
{
    return $user->id === $post->user_id;
}

public function delete(User $user, Post $post): bool
{
    return $user->id === $post->user_id;
}

// app/Policies/PostPolicy.php
public function deleteAny(User $user): bool
{
    return false;   // nadie borra en masa; el admin pasa por before()
}
}
