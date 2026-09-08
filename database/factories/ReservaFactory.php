<?php

namespace Database\Factories;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Reserva>
 */
class ReservaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = Carbon::instance(fake()->dateTimeBetween('+1 day', '+1 month'))->startOfHour();

        return [
            'sala_id' => Sala::factory(),
            'profesor_id' => User::factory(),
            'titulo' => fake()->sentence(4),
            'descripcion' => fake()->optional()->sentence(),
            'cantidad_asistentes' => fake()->numberBetween(1, 12),
            'inicio' => $inicio,
            'fin' => $inicio->copy()->addHour(),
            'estado' => ReservaEstado::Pendiente,
            'motivo_resolucion' => null,
            'resuelta_por_id' => null,
            'resuelta_at' => null,
        ];
    }

    public function aprobada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => ReservaEstado::Aprobada,
        ]);
    }

    public function rechazada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => ReservaEstado::Rechazada,
            'motivo_resolucion' => fake()->sentence(),
        ]);
    }
}
