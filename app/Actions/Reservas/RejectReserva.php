<?php

namespace App\Actions\Reservas;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ReservaRuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RejectReserva
{
    public function __construct(private ReservaRuleService $rules) {}

    public function handle(Reserva $reserva, User $administrator, string $motivo): Reserva
    {
        Gate::forUser($administrator)->authorize('reject', $reserva);

        return DB::transaction(function () use ($reserva, $administrator, $motivo): Reserva {
            $lockedReserva = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);
            $this->rules->ensurePending($lockedReserva);

            $lockedReserva->update([
                'estado' => ReservaEstado::Rechazada,
                'motivo_resolucion' => $motivo,
                'resuelta_por_id' => $administrator->id,
                'resuelta_at' => now(),
            ]);

            $lockedReserva->historial()->create([
                'actor_id' => $administrator->id,
                'estado_anterior' => ReservaEstado::Pendiente,
                'estado_nuevo' => ReservaEstado::Rechazada,
                'motivo' => $motivo,
            ]);

            return $lockedReserva->refresh();
        });
    }
}
