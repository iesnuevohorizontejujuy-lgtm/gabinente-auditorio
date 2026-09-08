<?php

namespace App\Actions\Reservas;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CancelReserva
{
    public function handle(Reserva $reserva, User $actor, ?string $motivo = null): Reserva
    {
        Gate::forUser($actor)->authorize('cancel', $reserva);

        return DB::transaction(function () use ($reserva, $actor, $motivo): Reserva {
            $lockedReserva = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);

            if (! in_array($lockedReserva->estado, [ReservaEstado::Pendiente, ReservaEstado::Aprobada], true)) {
                throw ValidationException::withMessages([
                    'reserva' => 'La reserva ya no puede cancelarse.',
                ]);
            }

            $previousStatus = $lockedReserva->estado;
            $lockedReserva->update([
                'estado' => ReservaEstado::Cancelada,
                'motivo_resolucion' => $motivo,
                'resuelta_por_id' => $actor->id,
                'resuelta_at' => now(),
            ]);

            $lockedReserva->historial()->create([
                'actor_id' => $actor->id,
                'estado_anterior' => $previousStatus,
                'estado_nuevo' => ReservaEstado::Cancelada,
                'motivo' => $motivo ?? 'Reserva cancelada.',
            ]);

            return $lockedReserva->refresh();
        });
    }
}
