<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Livewire\Concerns\SeleccionaFechaConCalendario;
use App\Models\Carrera;
use App\Models\Espacio;
use App\Models\Turno;
use App\Notifications\TurnoNotification;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditarTurno extends Component
{
    use WithFileUploads;
    use SeleccionaFechaConCalendario;

    public Turno $turno;

    public $carrera_id = '';
    public $fecha = '';
    public $hora_inicio = '';
    public $hora_fin = '';
    public $motivo = '';
    public $cantidad_asistentes_aproximada = '';
    public $curso = '';
    public $notaFormal = null;

    public array $horariosOcupados = [];
    public array $paquetes = [];

    /** @var array<int, array{valor: string, disponible: bool}> */
    public array $horasInicioDisponibles = [];

    /** @var array<int, array{valor: string, disponible: bool}> */
    public array $horasFinDisponibles = [];

    public ?string $mensajeExito = null;

    public function mount(Turno $turno): void
    {
        $this->authorize('update', $turno);

        $this->turno = $turno;
        $this->carrera_id = $turno->carrera_id ?? '';
        $this->fecha = $turno->fecha->format('Y-m-d');
        $this->hora_inicio = substr($turno->hora_inicio, 0, 5);
        $this->hora_fin = substr($turno->hora_fin, 0, 5);
        $this->motivo = $turno->motivo;
        $this->cantidad_asistentes_aproximada = $turno->cantidad_asistentes_aproximada;
        $this->curso = $turno->curso ?? '';

        $this->cargarDisponibilidad();

        if ($this->turno->espacio->tipo->usaPaquetesHorarios()) {
            $this->paquetes = $this->turno->espacio->paquetesDisponibles($this->fecha);

            $this->horasInicioDisponibles = collect($this->paquetes)
                ->unique('inicio')
                ->map(fn ($p) => [
                    'valor' => $p['inicio'],
                    'disponible' => $this->contarOcupados($p['inicio'], $p['fin']) < $this->turno->espacio->unidades_disponibles,
                ])
                ->values()
                ->all();

            $this->recalcularHorasFin();
        }

        $this->inicializarCalendario();
    }

    protected function espacioParaCalendario(): Espacio
    {
        return $this->turno->espacio;
    }

    public function updatedFecha(): void
    {
        $this->hora_inicio = '';
        $this->hora_fin = '';
        $this->horasFinDisponibles = [];
        $this->paquetes = [];
        $this->horasInicioDisponibles = [];
        $this->horariosOcupados = [];

        if (!$this->fecha) {
            return;
        }

        if (Turno::esFinDeSemana($this->fecha)) {
            $this->addError('fecha', 'Los sábados y domingos no están habilitados para reservas.');

            return;
        }

        $this->cargarDisponibilidad();

        if ($this->turno->espacio->tipo->usaPaquetesHorarios()) {
            $this->paquetes = $this->turno->espacio->paquetesDisponibles($this->fecha);

            $this->horasInicioDisponibles = collect($this->paquetes)
                ->unique('inicio')
                ->map(fn ($p) => [
                    'valor' => $p['inicio'],
                    'disponible' => $this->contarOcupados($p['inicio'], $p['fin']) < $this->turno->espacio->unidades_disponibles,
                ])
                ->values()
                ->all();
        }
    }

    public function updatedHoraInicio(): void
    {
        $this->hora_fin = '';
        $this->recalcularHorasFin();
    }

    /**
     * Cuenta cuántos turnos ya ocupados (pendientes o aprobados, excluyendo
     * este mismo turno) para esta fecha se superponen con el rango entre
     * $inicio y $fin (fin exclusivo).
     */
    protected function contarOcupados(string $inicio, string $fin): int
    {
        return collect($this->horariosOcupados)
            ->filter(function ($h) use ($inicio, $fin) {
                $hInicio = substr($h['hora_inicio'], 0, 5);
                $hFin = substr($h['hora_fin'], 0, 5);

                return $hInicio < $fin && $hFin > $inicio;
            })
            ->count();
    }

    protected function recalcularHorasFin(): void
    {
        if (!$this->turno->espacio->tipo->usaPaquetesHorarios() || !$this->hora_inicio) {
            $this->horasFinDisponibles = [];

            return;
        }

        $ventana = collect($this->paquetes)->firstWhere('inicio', $this->hora_inicio)['ventana'] ?? null;

        $this->horasFinDisponibles = collect($this->paquetes)
            ->where('ventana', $ventana)
            ->where('inicio', '>=', $this->hora_inicio)
            ->pluck('fin')
            ->unique()
            ->values()
            ->map(fn ($fin) => [
                'valor' => $fin,
                'disponible' => $this->contarOcupados($this->hora_inicio, $fin) < $this->turno->espacio->unidades_disponibles,
            ])
            ->all();
    }

    public function cargarDisponibilidad(): void
    {
        if (!$this->fecha) {
            $this->horariosOcupados = [];

            return;
        }

        $this->horariosOcupados = Turno::where('espacio_id', $this->turno->espacio_id)
            ->whereDate('fecha', $this->fecha)
            ->whereIn('estado', [EstadoTurno::PENDIENTE->value, EstadoTurno::APROBADO->value])
            ->where('id', '!=', $this->turno->id)
            ->orderBy('hora_inicio')
            ->get(['hora_inicio', 'hora_fin'])
            ->toArray();
    }

    protected function horasValidas(array $opciones): array
    {
        return collect($opciones)->where('disponible', true)->pluck('valor')->all();
    }

    protected function rules(): array
    {
        $tipo = $this->turno->espacio->tipo;

        $reglasHorario = $tipo->usaPaquetesHorarios()
            ? [
                'hora_inicio' => ['required', Rule::in($this->horasValidas($this->horasInicioDisponibles))],
                'hora_fin' => ['required', Rule::in($this->horasValidas($this->horasFinDisponibles))],
            ]
            : [
                'hora_inicio' => ['required', 'date_format:H:i'],
                'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            ];

        return array_filter([
            'carrera_id' => ['required', 'exists:carreras,id'],
            'fecha' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    if (Turno::esFinDeSemana($value)) {
                        $fail('Los sábados y domingos no están habilitados para reservas.');
                    }
                },
            ],
            ...$reglasHorario,
            'motivo' => ['required', 'string', 'max:255'],
            'cantidad_asistentes_aproximada' => $tipo->requiereCantidadAsistentes()
                ? array_filter([
                    'required',
                    'integer',
                    'min:1',
                    $this->turno->espacio->capacidad ? 'max:' . $this->turno->espacio->capacidad : null,
                ])
                : null,
            'curso' => ['required', Rule::in(Turno::CURSOS)],
            // Al editar, la nota formal es opcional: si no se sube una nueva, se conserva la que ya estaba.
            'notaFormal' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    protected function messages(): array
    {
        return [
            'cantidad_asistentes_aproximada.max' => 'La cantidad de asistentes supera la capacidad del espacio (' . $this->turno->espacio->capacidad . ').',
            'curso.required' => 'Elegí el curso.',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'hora_inicio.in' => 'Ese horario ya está reservado o dejó de estar disponible. Elegí otro.',
            'hora_fin.in' => 'Ese horario ya está reservado o dejó de estar disponible. Elegí otro.',
            'notaFormal.image' => 'El archivo de la nota formal tiene que ser una imagen (jpg, png, etc.).',
            'notaFormal.max' => 'La imagen de la nota formal no puede superar los 5 MB.',
        ];
    }

    public function actualizar(): void
    {
        // Vuelve a chequear el permiso: si desde que se abrió la pantalla pasó
        // la ventana de 24hs, ya no debería poder guardar el cambio.
        $this->authorize('update', $this->turno);

        $datos = $this->validate();
        unset($datos['notaFormal']);

        $tipo = $this->turno->espacio->tipo;
        $horasMinimas = $tipo->horasAnticipacionMinima();

        if (!Turno::cumpleAnticipacionMinima($this->fecha, $this->hora_inicio, $horasMinimas)) {
            $mensaje = $horasMinimas > 0
                ? 'La nueva fecha/horario debe respetar las ' . $horasMinimas . ' horas de anticipación.'
                : 'La fecha y horario elegidos ya pasaron. Elegí un horario futuro.';

            $this->addError('fecha', $mensaje);

            return;
        }

        $espacio = $this->turno->espacio;

        if (!$espacio->disponibleEn($this->fecha, $this->hora_inicio, $this->hora_fin)) {
            $this->addError(
                'hora_inicio',
                'El horario elegido está fuera de la disponibilidad habilitada por administración para este espacio.'
            );

            return;
        }

        if (!Turno::hayDisponibilidad($espacio, $this->fecha, $this->hora_inicio, $this->hora_fin, $this->turno->id)) {
            $this->addError('hora_inicio', 'Ya no hay disponibilidad para este espacio en el nuevo horario indicado.');

            return;
        }

        if ($this->notaFormal) {
            $datos['nota_formal_path'] = $this->notaFormal->store('notas-formales', 'public');
        }

        $volvioAPendiente = $this->turno->estado === EstadoTurno::APROBADO;

        $this->turno->update([
            ...$datos,
            'estado' => $volvioAPendiente ? EstadoTurno::PENDIENTE->value : $this->turno->estado->value,
        ]);

        $this->turno->docente->notify(new TurnoNotification($this->turno->fresh(), TurnoNotification::EVENTO_MODIFICADO));

        $this->mensajeExito = $volvioAPendiente
            ? 'Turno modificado. Como estaba aprobado, vuelve a quedar pendiente de revisión por administración.'
            : 'Turno modificado correctamente.';

        $this->notaFormal = null;
        $this->cargarDisponibilidad();
    }

    public function render()
    {
        return view('livewire.editar-turno', [
            'carreras' => Carrera::orderBy('nombre')->get(),
        ]);
    }
}
