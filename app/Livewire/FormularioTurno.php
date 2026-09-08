<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Enums\TipoEspacio;
use App\Models\Carrera;
use App\Models\Espacio;
use App\Models\Turno;
use App\Notifications\TurnoNotification;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class FormularioTurno extends Component
{
    use WithFileUploads;

    public TipoEspacio $tipo;
    public Espacio $espacio;

    public $carrera_id = '';
    public $fecha = '';
    public $hora_inicio = '';
    public $hora_fin = '';
    public $motivo = '';
    public $cantidad_asistentes_aproximada = '';
    public $curso = '';
    public $notaFormal = null;

    public bool $mostrarModalTerminos = false;
    public bool $terminosAceptados = false;

    public array $horariosOcupados = [];
    public array $paquetes = [];

    /** @var array<int, array{valor: string, disponible: bool}> */
    public array $horasInicioDisponibles = [];

    /** @var array<int, array{valor: string, disponible: bool}> */
    public array $horasFinDisponibles = [];

    public ?string $mensajeExito = null;

    public function mount(string $tipoEspacio): void
    {
        $this->authorize('create', Turno::class);

        $this->tipo = TipoEspacio::from($tipoEspacio);
        $this->espacio = Espacio::where('tipo', $this->tipo->value)->firstOrFail();
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

        // Primero cargamos los turnos ya existentes para esa fecha: los
        // paquetes que ya estén completos para esos horarios se marcan como
        // "reservado" en vez de dejarlos seleccionables.
        $this->cargarDisponibilidad();

        if ($this->tipo->usaPaquetesHorarios()) {
            $this->paquetes = $this->espacio->paquetesDisponibles($this->fecha);

            $this->horasInicioDisponibles = collect($this->paquetes)
                ->unique('inicio')
                ->map(fn ($p) => [
                    'valor' => $p['inicio'],
                    'disponible' => $this->contarOcupados($p['inicio'], $p['fin']) < $this->espacio->unidades_disponibles,
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
     * Cuenta cuántos turnos ya ocupados (pendientes o aprobados) para esta
     * fecha se superponen con el rango entre $inicio y $fin (fin exclusivo).
     * Trabaja en memoria sobre $horariosOcupados (ya cargado) para no volver
     * a consultar la BD por cada paquete.
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
        if (!$this->tipo->usaPaquetesHorarios() || !$this->hora_inicio) {
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
                'disponible' => $this->contarOcupados($this->hora_inicio, $fin) < $this->espacio->unidades_disponibles,
            ])
            ->all();
    }

    public function cargarDisponibilidad(): void
    {
        if (!$this->fecha) {
            $this->horariosOcupados = [];

            return;
        }

        $this->horariosOcupados = Turno::where('espacio_id', $this->espacio->id)
            ->whereDate('fecha', $this->fecha)
            ->whereIn('estado', [EstadoTurno::PENDIENTE->value, EstadoTurno::APROBADO->value])
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
        $reglasHorario = $this->tipo->usaPaquetesHorarios()
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
            'cantidad_asistentes_aproximada' => $this->tipo->requiereCantidadAsistentes()
                ? array_filter([
                    'required',
                    'integer',
                    'min:1',
                    $this->espacio->capacidad ? 'max:' . $this->espacio->capacidad : null,
                ])
                : null,
            'curso' => ['required', Rule::in(Turno::CURSOS)],
            'notaFormal' => $this->tipo->requiereNotaFormal()
                ? ['required', 'image', 'max:5120']
                : ['nullable'],
        ]);
    }

    protected function messages(): array
    {
        return [
            'cantidad_asistentes_aproximada.max' => 'La cantidad de asistentes supera la capacidad del espacio (' . $this->espacio->capacidad . ').',
            'curso.required' => 'Elegí el curso.',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'hora_inicio.in' => 'Ese horario ya está reservado o dejó de estar disponible. Elegí otro.',
            'hora_fin.in' => 'Ese horario ya está reservado o dejó de estar disponible. Elegí otro.',
            'notaFormal.required' => 'Adjuntá una imagen de la nota formal del trámite administrativo.',
            'notaFormal.image' => 'El archivo de la nota formal tiene que ser una imagen (jpg, png, etc.).',
            'notaFormal.max' => 'La imagen de la nota formal no puede superar los 5 MB.',
        ];
    }

    /**
     * Valida las reglas de negocio (anticipación mínima, horario habilitado por
     * administración y disponibilidad real). Si algo falla, agrega el error
     * correspondiente al campo indicado y devuelve false.
     */
    protected function pasaValidacionesDeNegocio(): bool
    {
        $horasMinimas = $this->tipo->horasAnticipacionMinima();

        if (!Turno::cumpleAnticipacionMinima($this->fecha, $this->hora_inicio, $horasMinimas)) {
            $mensaje = $horasMinimas > 0
                ? 'Las reservas deben solicitarse con al menos ' . $horasMinimas . ' horas de anticipación.'
                : 'La fecha y horario elegidos ya pasaron. Elegí un horario futuro.';

            $this->addError('fecha', $mensaje);

            return false;
        }

        if (!$this->espacio->disponibleEn($this->fecha, $this->hora_inicio, $this->hora_fin)) {
            $this->addError(
                'hora_inicio',
                'El horario elegido está fuera de la disponibilidad habilitada por administración para este espacio.'
            );

            return false;
        }

        if (!Turno::hayDisponibilidad($this->espacio, $this->fecha, $this->hora_inicio, $this->hora_fin)) {
            $mensaje = in_array($this->tipo, [TipoEspacio::TV_SMART, TipoEspacio::PROYECTOR], true)
                ? 'No quedan unidades disponibles en el horario indicado.'
                : 'Ya existe un turno pendiente o aprobado para este espacio en el horario indicado.';

            $this->addError('hora_inicio', $mensaje);

            return false;
        }

        return true;
    }

    /**
     * Valida el formulario y, si está todo correcto, abre el popup de condiciones de uso.
     * El guardado real ocurre recién en confirmarReserva(), una vez aceptadas las condiciones.
     */
    public function intentarGuardar(): void
    {
        $this->mensajeExito = null;
        $this->validate();

        if (!$this->pasaValidacionesDeNegocio()) {
            return;
        }

        $this->terminosAceptados = false;
        $this->mostrarModalTerminos = true;
    }

    public function cerrarModalTerminos(): void
    {
        $this->mostrarModalTerminos = false;
        $this->terminosAceptados = false;
    }

    /**
     * Confirma la aceptación de las condiciones de uso y guarda el turno.
     * Vuelve a correr todas las validaciones por las dudas haya pasado tiempo
     * (o se haya ocupado el espacio) entre que se abrió el popup y se confirmó.
     */
    public function confirmarReserva(): void
    {
        if (!$this->terminosAceptados) {
            $this->addError('terminosAceptados', 'Debés aceptar las condiciones de uso para continuar.');

            return;
        }

        $datos = $this->validate();
        unset($datos['notaFormal']);

        if (!$this->pasaValidacionesDeNegocio()) {
            $this->mostrarModalTerminos = false;

            return;
        }

        if ($this->tipo->requiereNotaFormal() && $this->notaFormal) {
            $datos['nota_formal_path'] = $this->notaFormal->store('notas-formales', 'public');
        }

        $turno = Turno::create([
            ...$datos,
            'espacio_id' => $this->espacio->id,
            'docente_id' => auth()->id(),
            'estado' => EstadoTurno::PENDIENTE->value,
            'terminos_aceptados' => true,
            'terminos_aceptados_en' => now(),
            'terminos_version' => config('reglas_uso.version'),
        ]);

        auth()->user()->notify(new TurnoNotification($turno, TurnoNotification::EVENTO_CREADO));

        $this->reset([
            'carrera_id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo', 'cantidad_asistentes_aproximada',
            'curso', 'notaFormal', 'mostrarModalTerminos', 'terminosAceptados', 'paquetes',
            'horasInicioDisponibles', 'horasFinDisponibles',
        ]);
        $this->horariosOcupados = [];
        $this->mensajeExito = 'Turno solicitado correctamente. Queda pendiente de aprobación por administración.';
    }

    public function render()
    {
        $reglasUso = config('reglas_uso.por_tipo.' . $this->tipo->value, config('reglas_uso.items'));

        if ($this->espacio->capacidad && $this->espacio->equipamiento) {
            array_unshift(
                $reglasUso,
                "La sala tiene una capacidad para {$this->espacio->capacidad} personas y dispone de {$this->espacio->equipamiento}."
            );
        }

        return view('livewire.formulario-turno', [
            'carreras' => Carrera::orderBy('nombre')->get(),
            'reglasUso' => $reglasUso,
        ]);
    }
}
