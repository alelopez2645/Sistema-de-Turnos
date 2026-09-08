<?php

namespace App\Livewire;

use App\Models\Carrera;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GestionCarreras extends Component
{
    public ?int $editandoId = null;
    public $nombre = '';

    public ?string $mensaje = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdministrador(), 403);
    }

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('carreras', 'nombre')->ignore($this->editandoId),
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una carrera con ese nombre.',
        ];
    }

    public function editar(int $id): void
    {
        $carrera = Carrera::findOrFail($id);

        $this->editandoId = $carrera->id;
        $this->nombre = $carrera->nombre;
    }

    public function cancelarEdicion(): void
    {
        $this->reset(['editandoId', 'nombre']);
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        if ($this->editandoId) {
            Carrera::findOrFail($this->editandoId)->update($datos);
            $this->mensaje = 'Carrera actualizada.';
        } else {
            Carrera::create($datos);
            $this->mensaje = 'Carrera creada.';
        }

        $this->cancelarEdicion();
    }

    public function eliminar(int $id): void
    {
        Carrera::findOrFail($id)->delete();

        // carrera_id usa nullOnDelete() tanto en users como en turnos, así
        // que esto no borra docentes ni turnos: solo los deja sin carrera.
        $this->mensaje = 'Carrera eliminada. Los docentes y turnos que la tenían asignada quedan sin carrera.';
    }

    public function render()
    {
        $carreras = Carrera::withCount(['docentes', 'turnos'])
            ->orderBy('nombre')
            ->get();

        return view('livewire.gestion-carreras', compact('carreras'));
    }
}
