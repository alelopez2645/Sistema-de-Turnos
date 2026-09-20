<?php

namespace App\Policies;

use App\Enums\EstadoTurno;
use App\Models\Turno;
use App\Models\User;

class TurnoPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // el controller filtra: admin ve todos, docente ve solo los propios
    }

    public function view(User $user, Turno $turno): bool
    {
        return $user->esAdministrador() || $turno->docente_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->esDocente() || $user->esAdministrador();
    }

    public function update(User $user, Turno $turno): bool
    {
        if ($user->esAdministrador()) {
            return true;
        }

        // el docente dueño del turno puede editarlo hasta 24hs antes del inicio del evento
        return $turno->docente_id === $user->id && $turno->puedeModificarse();
    }

    public function cancelar(User $user, Turno $turno): bool
    {
        $esDueñoPendiente = $turno->docente_id === $user->id
            && in_array($turno->estado, [EstadoTurno::PENDIENTE, EstadoTurno::APROBADO], true);

        return $user->esAdministrador() || $esDueñoPendiente;
    }

    public function aprobar(User $user, Turno $turno): bool
    {
        return $user->esAdministrador() && $turno->estado === EstadoTurno::PENDIENTE;
    }

    public function recepcionar(User $user, Turno $turno): bool
    {
        return ($turno->docente_id === $user->id || $user->esAdministrador())
            && $turno->puedeRecepcionarse();
    }

    public function entregar(User $user, Turno $turno): bool
    {
        return ($turno->docente_id === $user->id || $user->esAdministrador())
            && $turno->puedeEntregarse();
    }
}
