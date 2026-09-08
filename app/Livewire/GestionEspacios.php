<?php

namespace App\Livewire;

use App\Enums\TipoEspacio;
use App\Models\Espacio;
use Livewire\Component;

class GestionEspacios extends Component
{
    public array $capacidad = [];
    public array $unidadesDisponibles = [];
    public array $equipamiento = [];

    public ?string $mensaje = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdministrador(), 403);

        foreach (Espacio::all() as $espacio) {
            $this->capacidad[$espacio->id] = $espacio->capacidad;
            $this->unidadesDisponibles[$espacio->id] = $espacio->unidades_disponibles;
            $this->equipamiento[$espacio->id] = $espacio->equipamiento;
        }
    }

    public function guardar(int $espacioId): void
    {
        $espacio = Espacio::findOrFail($espacioId);

        // TV Smart y Proyector no manejan capacidad (no aplica un tope de "personas").
        $usaCapacidad = !in_array($espacio->tipo, [TipoEspacio::TV_SMART, TipoEspacio::PROYECTOR], true);

        $this->validate([
            'capacidad.' . $espacioId => $usaCapacidad ? ['required', 'integer', 'min:1'] : ['nullable'],
            'unidadesDisponibles.' . $espacioId => ['required', 'integer', 'min:1'],
            'equipamiento.' . $espacioId => ['nullable', 'string'],
        ]);

        $espacio->update([
            'capacidad' => $usaCapacidad ? $this->capacidad[$espacioId] : null,
            'unidades_disponibles' => $this->unidadesDisponibles[$espacioId],
            'equipamiento' => $this->equipamiento[$espacioId],
        ]);

        $this->mensaje = 'Espacio actualizado.';
    }

    public function render()
    {
        return view('livewire.gestion-espacios', [
            'espacios' => Espacio::orderBy('nombre')->get(),
        ]);
    }
}
