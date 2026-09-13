<?php

namespace App\Actions\Reservas;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ReservaRuleService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateReserva
{
    public function __construct(private ReservaRuleService $rules) {}

    /**
     * @param  array{titulo: string, inicio: string, fin: string}  $data
     */
    public function handle(Reserva $reserva, User $profesor, array $data): Reserva
    {
        Gate::forUser($profesor)->authorize('update', $reserva);

        return DB::transaction(function () use ($reserva, $profesor, $data): Reserva {
            $lockedReserva = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);
            Gate::forUser($profesor)->authorize('update', $lockedReserva);

            $inicio = Carbon::parse($data['inicio']);
            $fin = Carbon::parse($data['fin']);

            $this->rules->ensureCanBeRequested(
                $profesor,
                $lockedReserva->sala,
                $inicio,
                $fin,
            );

            $lockedReserva->update($data);
            $lockedReserva->historial()->create([
                'actor_id' => $profesor->id,
                'estado_anterior' => ReservaEstado::Pendiente,
                'estado_nuevo' => ReservaEstado::Pendiente,
                'motivo' => 'Solicitud actualizada.',
            ]);

            return $lockedReserva->refresh();
        });
    }
}
