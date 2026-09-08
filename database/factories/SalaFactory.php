<?php

namespace Database\Factories;

use App\Models\Sala;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sala>
 */
class SalaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(3, true),
            'descripcion' => fake()->optional()->sentence(),
            'ubicacion' => fake()->optional()->randomElement(['Planta baja', 'Primer piso', 'Segundo piso']),
            'capacidad' => fake()->numberBetween(8, 150),
            'activa' => true,
        ];
    }

    /**
     * Indicate that the room is inactive.
     */
    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activa' => false,
        ]);
    }
}
