<?php

namespace App\Policies;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\User;

class ReservaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->activo;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reserva $reserva): bool
    {
        return $user->activo
            && ($user->isAdministrator() || $reserva->profesor_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->activo && $user->isProfessor();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reserva $reserva): bool
    {
        return $this->view($user, $reserva)
            && $user->isProfessor()
            && $reserva->estado === ReservaEstado::Pendiente;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reserva $reserva): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Reserva $reserva): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Reserva $reserva): bool
    {
        return false;
    }

    public function approve(User $user, Reserva $reserva): bool
    {
        return $user->activo
            && $user->isAdministrator()
            && $reserva->estado === ReservaEstado::Pendiente;
    }

    public function reject(User $user, Reserva $reserva): bool
    {
        return $this->approve($user, $reserva);
    }

    public function cancel(User $user, Reserva $reserva): bool
    {
        if (! $user->activo || ! in_array($reserva->estado, [ReservaEstado::Pendiente, ReservaEstado::Aprobada], true)) {
            return false;
        }

        return $user->isAdministrator() || $reserva->profesor_id === $user->id;
    }
}
