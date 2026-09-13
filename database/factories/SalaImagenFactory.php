<?php

namespace Database\Factories;

use App\Models\Sala;
use App\Models\SalaImagen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaImagen>
 */
class SalaImagenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sala_id' => Sala::factory(),
            'ruta' => 'salas/'.fake()->uuid().'.jpg',
            'orden' => 0,
        ];
    }
}
