<?php

namespace App\Livewire;

use App\Models\Espacio;
use App\Models\HorarioDisponibilidad;
use Livewire\Component;

class GestionDisponibilidad extends Component
{
    public $espacio_id = '';
    public $dia_semana = '1';
    public $hora_inicio = '';
    public $hora_fin = '';

    public ?string $mensaje = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdministrador(), 403);
    }

    protected function rules(): array
    {
        return [
            'espacio_id' => ['required', 'exists:espacios,id'],
            'dia_semana' => ['required', 'integer', 'between:0,6'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function agregar(): void
    {
        $datos = $this->validate();

        HorarioDisponibilidad::create($datos + ['activo' => true]);

        $this->reset(['hora_inicio', 'hora_fin']);
        $this->mensaje = 'Franja horaria agregada.';
    }

    public function alternarActivo(int $id): void
    {
        $horario = HorarioDisponibilidad::findOrFail($id);
        $horario->update(['activo' => !$horario->activo]);
    }

    public function eliminar(int $id): void
    {
        HorarioDisponibilidad::findOrFail($id)->delete();

        $this->mensaje = 'Franja horaria eliminada.';
    }

    public function render()
    {
        return view('livewire.gestion-disponibilidad', [
            'espacios' => Espacio::orderBy('nombre')->get(),
            'horarios' => HorarioDisponibilidad::with('espacio')
                ->orderBy('espacio_id')
                ->orderBy('dia_semana')
                ->orderBy('hora_inicio')
                ->get(),
        ]);
    }
}
