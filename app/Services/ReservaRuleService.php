<?php

namespace App\Services;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

final class ReservaRuleService
{
    public function ensureCanBeRequested(
        User $profesor,
        Sala $sala,
        CarbonInterface $inicio,
        CarbonInterface $fin,
        int $cantidadAsistentes,
    ): void {
        if (! $profesor->activo || ! $profesor->isProfessor()) {
            throw ValidationException::withMessages([
                'salaId' => 'Sólo un profesor activo puede solicitar una reserva.',
            ]);
        }

        if (! $sala->activa) {
            throw ValidationException::withMessages([
                'salaId' => 'El espacio seleccionado no está disponible.',
            ]);
        }

        if ($inicio->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'inicio' => 'El inicio debe ser posterior al momento actual.',
            ]);
        }

        if ($fin->lessThanOrEqualTo($inicio)) {
            throw ValidationException::withMessages([
                'fin' => 'La finalización debe ser posterior al inicio.',
            ]);
        }

        $durationInMinutes = $inicio->diffInMinutes($fin);

        if ($durationInMinutes < 30 || $durationInMinutes > 480) {
            throw ValidationException::withMessages([
                'fin' => 'La reserva debe durar entre 30 minutos y 8 horas.',
            ]);
        }

        if ($cantidadAsistentes < 1 || $cantidadAsistentes > $sala->capacidad) {
            throw ValidationException::withMessages([
                'cantidadAsistentes' => "La capacidad máxima de este espacio es de {$sala->capacidad} personas.",
            ]);
        }
    }

    public function ensureCanBeApproved(Reserva $reserva): void
    {
        $reserva->loadMissing(['sala', 'profesor']);

        $this->ensureCanBeRequested(
            $reserva->profesor,
            $reserva->sala,
            $reserva->inicio,
            $reserva->fin,
            $reserva->cantidad_asistentes,
        );

        $hasOverlap = Reserva::query()
            ->aprobadas()
            ->whereBelongsTo($reserva->sala)
            ->whereKeyNot($reserva->id)
            ->entre($reserva->inicio, $reserva->fin)
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'reserva' => 'El espacio ya tiene una reserva aprobada en ese horario.',
            ]);
        }
    }

    public function ensurePending(Reserva $reserva): void
    {
        if ($reserva->estado !== ReservaEstado::Pendiente) {
            throw ValidationException::withMessages([
                'reserva' => 'La solicitud ya fue resuelta.',
            ]);
        }
    }
}
