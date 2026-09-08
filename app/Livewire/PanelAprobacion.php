<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Models\Turno;
use App\Notifications\TurnoNotification;
use Livewire\Component;
use Livewire\WithPagination;

class PanelAprobacion extends Component
{
    use WithPagination;

    public string $filtroEstado = 'pendiente';
    public array $observacionesPorTurno = [];
    public ?string $mensaje = null;

    public function updatingFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function aprobar(int $turnoId): void
    {
        $this->cambiarEstado($turnoId, EstadoTurno::APROBADO, TurnoNotification::EVENTO_APROBADO);
    }

    public function rechazar(int $turnoId): void
    {
        $this->cambiarEstado($turnoId, EstadoTurno::RECHAZADO, TurnoNotification::EVENTO_RECHAZADO);
    }

    public function cancelar(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('cancelar', $turno);

        $turno->update(['estado' => EstadoTurno::CANCELADO->value]);

        $turno->docente->notify(new TurnoNotification($turno->fresh(), TurnoNotification::EVENTO_CANCELADO));

        $this->mensaje = "Turno #{$turno->id} cancelado por administración.";
    }

    protected function cambiarEstado(int $turnoId, EstadoTurno $estado, string $evento): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('aprobar', $turno);

        $turno->update([
            'estado' => $estado->value,
            'observaciones' => $this->observacionesPorTurno[$turnoId] ?? $turno->observaciones,
        ]);

        $turno->docente->notify(new TurnoNotification($turno->fresh(), $evento));

        $this->mensaje = "Turno #{$turno->id} marcado como {$estado->value}.";
    }

    public function render()
    {
        $turnos = Turno::with(['espacio', 'docente', 'carrera'])
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->paginate(10);

        return view('livewire.panel-aprobacion', compact('turnos'));
    }
}
