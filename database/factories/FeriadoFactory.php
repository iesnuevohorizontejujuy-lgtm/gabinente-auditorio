<?php

namespace Database\Factories;

use App\Models\Feriado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feriado>
 */
class FeriadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->dateTimeBetween('now', '+1 year'),
            'nombre' => fake()->sentence(3),
            'tipo' => fake()->randomElement(['inamovible', 'trasladable']),
            'jurisdiccion' => 'nacional',
            'fuente' => 'https://argentinadatos.com',
        ];
    }
}
