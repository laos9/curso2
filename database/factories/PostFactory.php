<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    return [
        'titulo' => fake()->sentence(4),
        'contenido' => fake()->paragraph(),
        'categoria_id' => Categoria::factory(),
        'user_id' => User::factory(),
        'publicado' => true,
    ];
}
}
