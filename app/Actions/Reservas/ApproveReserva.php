<?php

namespace App\Actions\Reservas;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use App\Services\ReservaRuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ApproveReserva
{
    public function __construct(private ReservaRuleService $rules) {}

    public function handle(Reserva $reserva, User $administrator): Reserva
    {
        Gate::forUser($administrator)->authorize('approve', $reserva);

        return DB::transaction(function () use ($reserva, $administrator): Reserva {
            Sala::query()->whereKey($reserva->sala_id)->lockForUpdate()->firstOrFail();
            $lockedReserva = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);

            $this->rules->ensurePending($lockedReserva);
            $this->rules->ensureCanBeApproved($lockedReserva);

            $lockedReserva->update([
                'estado' => ReservaEstado::Aprobada,
                'motivo_resolucion' => null,
                'resuelta_por_id' => $administrator->id,
                'resuelta_at' => now(),
            ]);

            $lockedReserva->historial()->create([
                'actor_id' => $administrator->id,
                'estado_anterior' => ReservaEstado::Pendiente,
                'estado_nuevo' => ReservaEstado::Aprobada,
                'motivo' => 'Solicitud aprobada.',
            ]);

            return $lockedReserva->refresh();
        });
    }
}
