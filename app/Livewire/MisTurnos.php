<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Models\Turno;
use App\Notifications\TurnoNotification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MisTurnos extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?string $mensaje = null;

    /**
     * Id del turno para el que está abierto el modal de recepción o de
     * entrega (solo uno de los dos puede estar activo a la vez).
     */
    public ?int $turnoModalRecepcion = null;
    public ?int $turnoModalEntrega = null;

    public string $observacionesModal = '';

    /** @var array<int, mixed> archivos temporales subidos (TemporaryUploadedFile de Livewire) */
    public array $imagenesModal = [];

    public function cancelar(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('cancelar', $turno);

        $turno->update(['estado' => EstadoTurno::CANCELADO->value]);

        $turno->docente->notify(new TurnoNotification($turno->fresh(), TurnoNotification::EVENTO_CANCELADO));

        $this->mensaje = 'Turno cancelado.';
    }

    public function abrirRecepcion(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('recepcionar', $turno);

        $this->resetFormularioModal();
        $this->turnoModalRecepcion = $turnoId;
    }

    public function abrirEntrega(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('entregar', $turno);

        $this->resetFormularioModal();
        $this->turnoModalEntrega = $turnoId;
    }

    public function cerrarModal(): void
    {
        $this->resetFormularioModal();
    }

    protected function resetFormularioModal(): void
    {
        $this->turnoModalRecepcion = null;
        $this->turnoModalEntrega = null;
        $this->observacionesModal = '';
        $this->imagenesModal = [];
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'observacionesModal' => ['nullable', 'string', 'max:1000'],
            'imagenesModal' => ['nullable', 'array', 'max:5'],
            'imagenesModal.*' => ['image', 'max:5120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'imagenesModal.max' => 'Podés adjuntar hasta 5 imágenes.',
            'imagenesModal.*.image' => 'Cada archivo adjunto tiene que ser una imagen (jpg, png, etc.).',
            'imagenesModal.*.max' => 'Cada imagen no puede superar los 5 MB.',
        ];
    }

    public function confirmarRecepcion(): void
    {
        $datos = $this->validate();

        $turno = Turno::findOrFail($this->turnoModalRecepcion);

        $this->authorize('recepcionar', $turno);

        $rutas = collect($this->imagenesModal)
            ->map(fn ($imagen) => $imagen->store('recepciones', 'public'))
            ->values()
            ->all();

        $turno->update([
            'observaciones_recepcion' => $datos['observacionesModal'] ?: null,
            'imagenes_recepcion' => $rutas ?: null,
            'recepcionado_en' => now(),
        ]);

        $this->mensaje = 'Registraste la recepción del espacio.';
        $this->resetFormularioModal();
    }

    public function confirmarEntrega(): void
    {
        $datos = $this->validate();

        $turno = Turno::findOrFail($this->turnoModalEntrega);

        $this->authorize('entregar', $turno);

        $rutas = collect($this->imagenesModal)
            ->map(fn ($imagen) => $imagen->store('entregas', 'public'))
            ->values()
            ->all();

        $turno->update([
            'observaciones_entrega' => $datos['observacionesModal'] ?: null,
            'imagenes_entrega' => $rutas ?: null,
            'entregado_en' => now(),
        ]);

        $this->mensaje = 'Registraste la entrega del espacio.';
        $this->resetFormularioModal();
    }

    public function render()
    {
        $turnos = Turno::with(['espacio', 'carrera'])
            ->where('docente_id', auth()->id())
            ->orderByDesc('fecha')
            ->paginate(10);

        $turnoModalId = $this->turnoModalRecepcion ?? $this->turnoModalEntrega;
        $turnoEnModal = $turnoModalId ? Turno::with('espacio')->find($turnoModalId) : null;

        return view('livewire.mis-turnos', [
            'turnos' => $turnos,
            'turnoEnModal' => $turnoEnModal,
        ]);
    }
}
