<?php

namespace Database\Factories;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\ReservaHistorial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservaHistorial>
 */
class ReservaHistorialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'actor_id' => User::factory(),
            'estado_anterior' => ReservaEstado::Pendiente,
            'estado_nuevo' => ReservaEstado::Aprobada,
            'motivo' => null,
        ];
    }
}
