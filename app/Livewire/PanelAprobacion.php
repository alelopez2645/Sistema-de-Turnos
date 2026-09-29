<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Models\Espacio;
use App\Models\Turno;
use App\Notifications\TurnoNotification;
use Livewire\Component;
use Livewire\WithPagination;

class PanelAprobacion extends Component
{
    use WithPagination;

    public string $filtroEstado = 'pendiente';
    public string $filtroEspacio = '';
    public array $observacionesPorTurno = [];
    public ?string $mensaje = null;

    public function updatingFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEspacio(): void
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

        TurnoNotification::enviar($turno->docente, $turno->fresh(), TurnoNotification::EVENTO_CANCELADO);

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

        TurnoNotification::enviar($turno->docente, $turno->fresh(), $evento);

        $this->mensaje = "Turno #{$turno->id} marcado como {$estado->value}.";
    }

    public function render()
    {
        $turnos = Turno::with(['espacio', 'docente', 'carrera'])
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroEspacio, fn ($q) => $q->where('espacio_id', $this->filtroEspacio))
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->paginate(10);

        $espacios = Espacio::orderBy('nombre')->get(['id', 'nombre']);

        return view('livewire.panel-aprobacion', compact('turnos', 'espacios'));
    }
}
