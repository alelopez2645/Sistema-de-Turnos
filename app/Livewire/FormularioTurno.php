<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Enums\TipoEspacio;
use App\Models\Carrera;
use App\Models\Espacio;
use App\Models\Turno;
use App\Notifications\TurnoNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class FormularioTurno extends Component
{
    use WithFileUploads;

    public TipoEspacio $tipo;
    public Espacio $espacio;

    /** Lunes (Y-m-d) de la semana que se está mostrando en el calendario. */
    public string $semanaInicio = '';

    // --- Datos de la reserva en curso (se completan al arrastrar en el calendario) ---
    public $carrera_id = '';
    public $fecha = '';
    public $hora_inicio = '';
    public $hora_fin = '';
    public $motivo = '';
    public $cantidad_asistentes_aproximada = '';
    public $curso = '';
    public $notaFormal = null;

    public bool $mostrarModalReserva = false;
    public bool $mostrarModalTerminos = false;
    public bool $terminosAceptados = false;

    public ?string $mensajeExito = null;

    public function mount(string $tipoEspacio): void
    {
        $this->authorize('create', Turno::class);

        $this->tipo = TipoEspacio::from($tipoEspacio);
        $this->espacio = Espacio::where('tipo', $this->tipo->value)->firstOrFail();

        $this->semanaInicio = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    // ------------------------------------------------------------------
    // Navegación de semana
    // ------------------------------------------------------------------

    public function semanaAnterior(): void
    {
        $this->semanaInicio = Carbon::parse($this->semanaInicio)->subWeek()->toDateString();
    }

    public function semanaSiguiente(): void
    {
        $this->semanaInicio = Carbon::parse($this->semanaInicio)->addWeek()->toDateString();
    }

    public function irAHoy(): void
    {
        $this->semanaInicio = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    // ------------------------------------------------------------------
    // Construcción del calendario (días, filas horarias, ocupación)
    // ------------------------------------------------------------------

    protected const DIAS_LABEL = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    /**
     * @return array<int, array{fecha: string, etiqueta: string, diaMes: string, esHoy: bool, esFinDeSemana: bool}>
     */
    protected function diasSemana(): array
    {
        $inicio = Carbon::parse($this->semanaInicio);
        $hoy = now()->toDateString();

        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $fecha = $inicio->copy()->addDays($i);

            $dias[] = [
                'fecha' => $fecha->toDateString(),
                'etiqueta' => self::DIAS_LABEL[$i],
                'diaMes' => $fecha->format('d/m'),
                'esHoy' => $fecha->toDateString() === $hoy,
                'esFinDeSemana' => Turno::esFinDeSemana($fecha->toDateString()),
            ];
        }

        return $dias;
    }

    /**
     * Paquetes horarios habilitados por administración para cada día de la
     * semana mostrada (vacío para sábados/domingos o días sin franja activa).
     *
     * @return array<string, array<int, array{inicio: string, fin: string}>>
     */
    protected function paquetesPorDia(array $dias): array
    {
        $resultado = [];

        foreach ($dias as $dia) {
            $resultado[$dia['fecha']] = $this->espacio->paquetesDisponibles($dia['fecha']);
        }

        return $resultado;
    }

    /**
     * Eje horario de filas del calendario: la unión de todos los horarios de
     * inicio de paquete que aparecen en la semana, ordenados. Cada fila
     * representa un bloque de intervaloMinutos() (30' para Auditorio/Sala de
     * Capacitación, 40' para Informática/TV Smart/Proyector).
     *
     * @return array<int, array{inicio: string, fin: string}>
     */
    protected function filasHorario(array $paquetesPorDia): array
    {
        $filas = [];

        foreach ($paquetesPorDia as $paquetes) {
            foreach ($paquetes as $paquete) {
                $filas[$paquete['inicio']] = $paquete['fin'];
            }
        }

        ksort($filas);

        return collect($filas)
            ->map(fn ($fin, $inicio) => ['inicio' => $inicio, 'fin' => $fin])
            ->values()
            ->all();
    }

    /**
     * Turnos (pendientes o aprobados) que caen dentro de la semana mostrada,
     * con los datos del docente y la carrera para poder mostrarlos en el
     * calendario y en el listado de reservas de la semana (visible para
     * cualquier docente, no solo las propias).
     */
    protected function turnosSemana(array $dias): Collection
    {
        return Turno::with(['docente:id,name', 'carrera:id,nombre'])
            ->where('espacio_id', $this->espacio->id)
            ->whereBetween('fecha', [$dias[0]['fecha'], $dias[6]['fecha']])
            ->whereIn('estado', [EstadoTurno::PENDIENTE->value, EstadoTurno::APROBADO->value])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get(['id', 'espacio_id', 'fecha', 'hora_inicio', 'hora_fin', 'estado', 'docente_id', 'carrera_id', 'motivo']);
    }

    /**
     * Arma la matriz completa de celdas del calendario: para cada
     * combinación (día, fila horaria) calcula si cae dentro de una franja
     * habilitada, cuántas unidades están ocupadas, si ya pasó, si respeta la
     * anticipación mínima y si en definitiva se puede seleccionar. El
     * contenido visual de las reservas (docente, carrera, motivo) se arma
     * aparte, en eventosPorDia(), como bloques que abarcan todo el período
     * reservado.
     *
     * @return array<string, array<string, array{
     *     disponibleEnAgenda: bool, ocupados: int, capacidad: int,
     *     pasado: bool, seleccionable: bool
     * }>>
     */
    protected function construirBloques(array $dias, array $paquetesPorDia, Collection $turnos): array
    {
        $horasAnticipacion = $this->tipo->horasAnticipacionMinima();
        $bloques = [];

        foreach ($dias as $dia) {
            $fecha = $dia['fecha'];
            $paquetesDelDia = collect($paquetesPorDia[$fecha])->keyBy('inicio');
            $turnosDelDia = $turnos->filter(fn ($t) => $t->fecha->toDateString() === $fecha);

            $bloques[$fecha] = [];

            foreach ($this->filasHorario($paquetesPorDia) as $fila) {
                $inicio = $fila['inicio'];
                $fin = $fila['fin'];

                $disponibleEnAgenda = $paquetesDelDia->has($inicio) && $paquetesDelDia[$inicio]['fin'] === $fin;

                $ocupados = $turnosDelDia->filter(function ($t) use ($inicio, $fin) {
                    $tInicio = substr($t->hora_inicio, 0, 5);
                    $tFin = substr($t->hora_fin, 0, 5);

                    return $tInicio < $fin && $tFin > $inicio;
                })->count();

                $pasado = Carbon::parse($fecha . ' ' . $inicio)->isPast();
                $cumpleAnticipacion = Turno::cumpleAnticipacionMinima($fecha, $inicio, $horasAnticipacion);

                $bloques[$fecha][$inicio] = [
                    'disponibleEnAgenda' => $disponibleEnAgenda,
                    'ocupados' => $ocupados,
                    'capacidad' => $this->espacio->unidades_disponibles,
                    'pasado' => $pasado,
                    'seleccionable' => $disponibleEnAgenda && !$pasado && $cumpleAnticipacion && $ocupados < $this->espacio->unidades_disponibles,
                ];
            }
        }

        return $bloques;
    }

    /**
     * Arma, para cada día de la semana, un bloque visual por cada reserva
     * que ocupa varias filas contiguas del calendario ("grid-row: span N"),
     * con todo su contenido (horario, docente, carrera, motivo) adentro en
     * vez de repetirlo en cada franja. Cuando en un mismo horario hay más de
     * una reserva simultánea (TV Smart / Proyector / Informática, que
     * admiten varias unidades), se reparten en "carriles" lado a lado.
     *
     * @return array<string, array<int, array{
     *     filaInicio: int, span: int, lane: int, lanes: int, horario: string,
     *     docente: string, carrera: string, motivo: string, estado: string, esPropio: bool
     * }>>
     */
    protected function eventosPorDia(array $dias, array $filas, Collection $turnos): array
    {
        $indicePorInicio = [];
        $indicePorFin = [];
        foreach ($filas as $i => $f) {
            $indicePorInicio[$f['inicio']] = $i;
            $indicePorFin[$f['fin']] = $i;
        }

        $userId = auth()->id();
        $resultado = [];

        foreach ($dias as $dia) {
            $fecha = $dia['fecha'];

            $eventos = $turnos->filter(fn ($t) => $t->fecha->toDateString() === $fecha)
                ->sortBy('hora_inicio')
                ->values()
                ->map(function ($t) use ($indicePorInicio, $indicePorFin, $filas, $userId) {
                    $inicioStr = substr($t->hora_inicio, 0, 5);
                    $finStr = substr($t->hora_fin, 0, 5);

                    $filaInicio = $indicePorInicio[$inicioStr] ?? null;
                    $filaFin = $indicePorFin[$finStr] ?? null;

                    // Defensivo: si por algún motivo el horario guardado no calza
                    // exacto con ninguna fila del eje actual (p. ej. cambió la
                    // disponibilidad después de creado el turno), lo ubicamos
                    // igual dentro del rango de filas que abarca.
                    if ($filaInicio === null || $filaFin === null) {
                        $filaInicio = $filaInicio ?? 0;
                        $filaFin = $filaInicio;
                        foreach ($filas as $i => $f) {
                            if ($f['inicio'] >= $inicioStr && $f['fin'] <= $finStr) {
                                $filaFin = $i;
                            }
                        }
                    }

                    return [
                        'turno_id' => $t->id,
                        'filaInicio' => $filaInicio,
                        'filaFin' => $filaFin,
                        'span' => max(1, $filaFin - $filaInicio + 1),
                        'lane' => 0,
                        'lanes' => 1,
                        'horario' => $inicioStr . ' - ' . $finStr,
                        'docente' => $t->docente?->name ?? 'Docente',
                        'carrera' => $t->carrera?->nombre ?? 'Sin carrera',
                        'motivo' => $t->motivo,
                        'estado' => $t->estado->value,
                        'esPropio' => $t->docente_id === $userId,
                    ];
                })
                ->values()
                ->all();

            $resultado[$fecha] = $this->asignarCarriles($eventos);
        }

        return $resultado;
    }

    /**
     * Asigna "carriles" (columnas dentro del día) a una lista de eventos
     * para que los que se solapan en el tiempo se muestren lado a lado en
     * vez de superpuestos, y agrupa los eventos en "clusters" de
     * solapamiento mutuo para que todos los eventos de un mismo cluster
     * usen el mismo ancho de carril.
     *
     * @param  array<int, array{filaInicio: int, filaFin: int}>  $eventos
     * @return array<int, array{filaInicio: int, filaFin: int, lane: int, lanes: int}>
     */
    protected function asignarCarriles(array $eventos): array
    {
        if (count($eventos) <= 1) {
            return $eventos;
        }

        usort($eventos, fn ($a, $b) => $a['filaInicio'] <=> $b['filaInicio']);

        $seSolapan = fn (array $a, array $b) => $a['filaInicio'] <= $b['filaFin'] && $a['filaFin'] >= $b['filaInicio'];

        // 1) Carril: el primer carril libre (cuyo último evento ya terminó
        //    antes de que empiece este) para cada evento, en orden de inicio.
        $finPorCarril = [];
        foreach ($eventos as &$evento) {
            $asignado = false;

            foreach ($finPorCarril as $carril => $filaFinOcupada) {
                if ($evento['filaInicio'] > $filaFinOcupada) {
                    $evento['lane'] = $carril;
                    $finPorCarril[$carril] = $evento['filaFin'];
                    $asignado = true;
                    break;
                }
            }

            if (!$asignado) {
                $evento['lane'] = count($finPorCarril);
                $finPorCarril[] = $evento['filaFin'];
            }
        }
        unset($evento);

        // 2) Clusters: agrupa eventos conectados transitivamente por
        //    solapamiento, para que todos los de un mismo cluster usen la
        //    misma cantidad total de carriles (mismo ancho).
        $pendientes = array_keys($eventos);

        while ($pendientes) {
            $pila = [array_shift($pendientes)];
            $cluster = [];

            while ($pila) {
                $actual = array_pop($pila);
                if (in_array($actual, $cluster, true)) {
                    continue;
                }
                $cluster[] = $actual;

                foreach ($pendientes as $k => $idx) {
                    if ($seSolapan($eventos[$actual], $eventos[$idx])) {
                        $pila[] = $idx;
                        unset($pendientes[$k]);
                    }
                }
                $pendientes = array_values($pendientes);
            }

            $maxLane = 0;
            foreach ($cluster as $idx) {
                $maxLane = max($maxLane, $eventos[$idx]['lane']);
            }
            foreach ($cluster as $idx) {
                $eventos[$idx]['lanes'] = $maxLane + 1;
            }
        }

        return array_values($eventos);
    }

    /**
     * Reservas (de cualquier docente) de la semana mostrada, ordenadas por
     * fecha y horario, para el listado debajo del calendario.
     */
    protected function reservasSemana(Collection $turnos): Collection
    {
        return $turnos->sortBy(fn ($t) => $t->fecha->format('Y-m-d') . ' ' . $t->hora_inicio)->values();
    }

    // ------------------------------------------------------------------
    // Selección por arrastre -> formulario flotante
    // ------------------------------------------------------------------

    /**
     * Llamado desde el JS del calendario (Alpine) una única vez, al soltar
     * el arrastre. $horaInicio/$horaFin ya vienen recortados en el cliente
     * para no cruzar celdas no seleccionables, pero igual se revalida todo
     * en el servidor antes de guardar (ver rules()/pasaValidacionesDeNegocio()).
     */
    public function abrirNuevaReserva(string $fecha, string $horaInicio, string $horaFin): void
    {
        $this->resetErrorBag();
        $this->mensajeExito = null;

        $this->fecha = $fecha;
        $this->hora_inicio = $horaInicio;
        $this->hora_fin = $horaFin;
        $this->motivo = '';
        $this->carrera_id = '';
        $this->curso = '';
        $this->cantidad_asistentes_aproximada = '';
        $this->notaFormal = null;

        $this->mostrarModalReserva = true;
    }

    public function cerrarModalReserva(): void
    {
        $this->mostrarModalReserva = false;
        $this->resetErrorBag();
        $this->reset([
            'fecha', 'hora_inicio', 'hora_fin', 'motivo',
            'carrera_id', 'curso', 'cantidad_asistentes_aproximada', 'notaFormal',
        ]);
    }

    // ------------------------------------------------------------------
    // Validación
    // ------------------------------------------------------------------

    /**
     * Indica si [$horaInicio, $horaFin) es exactamente una sucesión
     * contigua de paquetes habilitados por administración para $fecha (sin
     * saltos ni cruces de franja, p. ej. el corte del mediodía).
     */
    protected function rangoValido(string $fecha, string $horaInicio, string $horaFin): bool
    {
        $paquetes = collect($this->espacio->paquetesDisponibles($fecha))
            ->filter(fn ($p) => $p['inicio'] >= $horaInicio && $p['fin'] <= $horaFin)
            ->sortBy('inicio')
            ->values();

        if ($paquetes->isEmpty()) {
            return false;
        }

        if ($paquetes->first()['inicio'] !== $horaInicio || $paquetes->last()['fin'] !== $horaFin) {
            return false;
        }

        return $paquetes->slice(1)->values()
            ->every(fn ($paquete, $i) => $paquete['inicio'] === $paquetes[$i]['fin']);
    }

    protected function rules(): array
    {
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
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => [
                'required',
                'date_format:H:i',
                'after:hora_inicio',
                function ($attribute, $value, $fail) {
                    if ($this->fecha && !Turno::esFinDeSemana($this->fecha) && !$this->rangoValido($this->fecha, $this->hora_inicio, $value)) {
                        $fail('Ese horario no corresponde a una franja habilitada por administración.');
                    }
                },
            ],
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
            'notaFormal.required' => 'Adjuntá una imagen de la nota formal del trámite administrativo.',
            'notaFormal.image' => 'El archivo de la nota formal tiene que ser una imagen (jpg, png, etc.).',
            'notaFormal.max' => 'La imagen de la nota formal no puede superar los 5 MB.',
        ];
    }

    /**
     * Valida las reglas de negocio (anticipación mínima y disponibilidad
     * real al momento de confirmar, por si cambió algo desde que se abrió
     * el formulario).
     */
    protected function pasaValidacionesDeNegocio(): bool
    {
        $horasMinimas = $this->tipo->horasAnticipacionMinima();

        if (!Turno::cumpleAnticipacionMinima($this->fecha, $this->hora_inicio, $horasMinimas)) {
            $mensaje = $horasMinimas > 0
                ? 'Las reservas deben solicitarse con al menos ' . $horasMinimas . ' horas de anticipación.'
                : 'La fecha y horario elegidos ya pasaron. Elegí un horario futuro.';

            $this->addError('fecha', $mensaje);
            $this->mostrarModalReserva = true;

            return false;
        }

        if (!$this->espacio->disponibleEn($this->fecha, $this->hora_inicio, $this->hora_fin)) {
            $this->addError(
                'hora_inicio',
                'El horario elegido está fuera de la disponibilidad habilitada por administración para este espacio.'
            );
            $this->mostrarModalReserva = true;

            return false;
        }

        if (!Turno::hayDisponibilidad($this->espacio, $this->fecha, $this->hora_inicio, $this->hora_fin)) {
            $mensaje = in_array($this->tipo, [TipoEspacio::TV_SMART, TipoEspacio::PROYECTOR], true)
                ? 'No quedan unidades disponibles en el horario indicado.'
                : 'Ya existe un turno pendiente o aprobado para este espacio en el horario indicado.';

            $this->addError('hora_inicio', $mensaje);
            $this->mostrarModalReserva = true;

            return false;
        }

        return true;
    }

    /**
     * Valida el formulario del popup "Nueva Reserva" y, si está todo
     * correcto, lo reemplaza por el popup de condiciones de uso. El
     * guardado real ocurre recién en confirmarReserva().
     */
    public function intentarGuardar(): void
    {
        $this->mensajeExito = null;
        $this->validate();

        if (!$this->pasaValidacionesDeNegocio()) {
            return;
        }

        $this->terminosAceptados = false;
        $this->mostrarModalReserva = false;
        $this->mostrarModalTerminos = true;
    }

    public function cerrarModalTerminos(): void
    {
        $this->mostrarModalTerminos = false;
        $this->terminosAceptados = false;
        $this->mostrarModalReserva = true;
    }

    /**
     * Confirma la aceptación de las condiciones de uso y guarda el turno.
     * Vuelve a correr todas las validaciones por si pasó tiempo (o se ocupó
     * el espacio) entre que se abrió el popup y se confirmó.
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

        $estadoInicial = $this->tipo->requiereAprobacion() ? EstadoTurno::PENDIENTE : EstadoTurno::APROBADO;

        $turno = Turno::create([
            ...$datos,
            'espacio_id' => $this->espacio->id,
            'docente_id' => auth()->id(),
            'estado' => $estadoInicial->value,
            'terminos_aceptados' => true,
            'terminos_aceptados_en' => now(),
            'terminos_version' => config('reglas_uso.version'),
        ]);

        auth()->user()->notify(new TurnoNotification($turno, TurnoNotification::EVENTO_CREADO));

        $this->reset([
            'carrera_id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo',
            'cantidad_asistentes_aproximada', 'curso', 'notaFormal',
            'mostrarModalReserva', 'mostrarModalTerminos', 'terminosAceptados',
        ]);
        $this->mensajeExito = $this->tipo->requiereAprobacion()
            ? 'Turno solicitado correctamente. Queda pendiente de aprobación por administración.'
            : 'Turno reservado correctamente.';
    }

    /**
     * Formatea una hora "H:i" (24hs) como "09:00 a.m." / "02:30 p.m.", para
     * mostrarla en el popup de "Nueva Reserva".
     */
    public function formatoAmPm(string $hora): string
    {
        $c = Carbon::createFromFormat('H:i', $hora);
        $sufijo = $c->format('A') === 'AM' ? 'a.m.' : 'p.m.';

        return $c->format('h:i') . ' ' . $sufijo;
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

        $dias = $this->diasSemana();
        $paquetesPorDia = $this->paquetesPorDia($dias);
        $filas = $this->filasHorario($paquetesPorDia);
        $turnos = $this->turnosSemana($dias);
        $bloques = $this->construirBloques($dias, $paquetesPorDia, $turnos);
        $eventosPorDia = $this->eventosPorDia($dias, $filas, $turnos);

        return view('livewire.formulario-turno', [
            'carreras' => Carrera::orderBy('nombre')->get(),
            'reglasUso' => $reglasUso,
            'dias' => $dias,
            'filas' => $filas,
            'bloques' => $bloques,
            'eventosPorDia' => $eventosPorDia,
            'reservasSemana' => $this->reservasSemana($turnos),
            'rangoSemana' => Carbon::parse($dias[0]['fecha'])->format('d/m') . ' – ' . Carbon::parse($dias[6]['fecha'])->format('d/m/Y'),
        ]);
    }
}
