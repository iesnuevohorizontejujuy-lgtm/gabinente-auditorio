<?php

namespace App\Actions\Reservas;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use App\Services\ReservaRuleService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

final class CreateReserva
{
    public function __construct(private ReservaRuleService $rules) {}

    /**
     * @param  array{sala_id: int, titulo: string, descripcion: string|null, cantidad_asistentes: int, inicio: string, fin: string}  $data
     */
    public function handle(User $profesor, array $data): Reserva
    {
        Gate::forUser($profesor)->authorize('create', Reserva::class);

        $sala = Sala::query()->findOrFail($data['sala_id']);
        $inicio = Carbon::parse($data['inicio']);
        $fin = Carbon::parse($data['fin']);

        $this->rules->ensureCanBeRequested($profesor, $sala, $inicio, $fin, $data['cantidad_asistentes']);

        $reserva = Reserva::query()->create([
            ...$data,
            'profesor_id' => $profesor->id,
            'estado' => ReservaEstado::Pendiente,
        ]);

        $reserva->historial()->create([
            'actor_id' => $profesor->id,
            'estado_anterior' => null,
            'estado_nuevo' => ReservaEstado::Pendiente,
            'motivo' => 'Solicitud creada.',
        ]);

        return $reserva;
    }
}
