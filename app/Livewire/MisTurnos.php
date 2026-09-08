<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Models\Turno;
use App\Notifications\TurnoNotification;
use Livewire\Component;
use Livewire\WithPagination;

class MisTurnos extends Component
{
    use WithPagination;

    public ?string $mensaje = null;

    public array $observacionesRecepcionPorTurno = [];
    public array $observacionesEntregaPorTurno = [];

    public function cancelar(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('cancelar', $turno);

        $turno->update(['estado' => EstadoTurno::CANCELADO->value]);

        $turno->docente->notify(new TurnoNotification($turno->fresh(), TurnoNotification::EVENTO_CANCELADO));

        $this->mensaje = 'Turno cancelado.';
    }

    public function recepcionar(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('recepcionar', $turno);

        $turno->update([
            'observaciones_recepcion' => $this->observacionesRecepcionPorTurno[$turnoId] ?? null,
            'recepcionado_en' => now(),
        ]);

        $this->mensaje = 'Registraste la recepción del espacio.';
    }

    public function entregar(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('entregar', $turno);

        $turno->update([
            'observaciones_entrega' => $this->observacionesEntregaPorTurno[$turnoId] ?? null,
            'entregado_en' => now(),
        ]);

        $this->mensaje = 'Registraste la entrega del espacio.';
    }

    public function render()
    {
        $turnos = Turno::with(['espacio', 'carrera'])
            ->where('docente_id', auth()->id())
            ->orderByDesc('fecha')
            ->paginate(10);

        return view('livewire.mis-turnos', compact('turnos'));
    }
}
