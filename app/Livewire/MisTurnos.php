<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Models\Turno;
use App\Notifications\TurnoNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

class MisTurnos extends Component
{
    use WithFileUploads;

    public ?string $mensaje = null;

    /** Lunes (Y-m-d) de la semana que se está mostrando. */
    public string $semanaInicio = '';

    /** Id del turno para el que está abierto el popup de detalle (info + botones). */
    public ?int $turnoDetalle = null;

    /**
     * Id del turno para el que está abierto el modal de recepción o de
     * entrega (solo uno de los dos puede estar activo a la vez).
     */
    public ?int $turnoModalRecepcion = null;
    public ?int $turnoModalEntrega = null;

    public string $observacionesModal = '';

    /** @var array<int, mixed> archivos temporales subidos (TemporaryUploadedFile de Livewire) */
    public array $imagenesModal = [];

    // Ventana del calendario: mismo horario operativo que la vista de reserva.
    protected const HORA_INICIO_CALENDARIO = 8;
    protected const HORA_FIN_CALENDARIO = 21;
    protected const PX_POR_HORA = 48;

    protected const DIAS_LABEL = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    public function mount(): void
    {
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
    // Popup de detalle (se abre al hacer clic en un turno del calendario)
    // ------------------------------------------------------------------

    public function abrirDetalle(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('view', $turno);

        $this->turnoDetalle = $turnoId;
    }

    public function cerrarDetalle(): void
    {
        $this->turnoDetalle = null;
    }

    // ------------------------------------------------------------------
    // Acciones sobre un turno (se conservan tal cual: editar es un link
    // directo a la pantalla de edición, y cancelar/recepción/entrega son
    // las mismas de siempre, ahora disparadas desde el popup de detalle).
    // ------------------------------------------------------------------

    public function cancelar(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('cancelar', $turno);

        $turno->update(['estado' => EstadoTurno::CANCELADO->value]);

        TurnoNotification::enviar($turno->docente, $turno->fresh(), TurnoNotification::EVENTO_CANCELADO);

        $this->turnoDetalle = null;
        $this->mensaje = 'Turno cancelado.';
    }

    public function abrirRecepcion(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('recepcionar', $turno);

        $this->resetFormularioModal();
        $this->turnoDetalle = null;
        $this->turnoModalRecepcion = $turnoId;
    }

    public function abrirEntrega(int $turnoId): void
    {
        $turno = Turno::findOrFail($turnoId);

        $this->authorize('entregar', $turno);

        $this->resetFormularioModal();
        $this->turnoDetalle = null;
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

    // ------------------------------------------------------------------
    // Construcción del calendario
    // ------------------------------------------------------------------

    /**
     * @return array<int, array{fecha: string, etiqueta: string, diaMes: string, esHoy: bool}>
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
            ];
        }

        return $dias;
    }

    /**
     * Todos los turnos (de cualquier espacio y cualquier estado) del
     * docente logueado que caen en la semana mostrada.
     */
    protected function turnosSemana(array $dias): Collection
    {
        return Turno::with(['espacio', 'carrera'])
            ->where('docente_id', auth()->id())
            ->whereBetween('fecha', [$dias[0]['fecha'], $dias[6]['fecha']])
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Arma, por día, los bloques a dibujar: posición y alto en píxeles
     * (según su horario real, sin depender de una grilla de franjas fija,
     * porque acá conviven turnos de espacios con distinta duración de
     * módulo) más el contenido a mostrar. Cuando dos turnos del mismo
     * docente se superponen en el tiempo (espacios distintos reservados a
     * la misma hora), se reparten en carriles lado a lado.
     *
     * @return array<string, array<int, array{
     *     turno_id: int, top: float, alto: float, lane: int, lanes: int,
     *     espacio: string, horario: string, estado: string
     * }>>
     */
    protected function eventosPorDia(array $dias, Collection $turnos): array
    {
        $inicioMin = self::HORA_INICIO_CALENDARIO * 60;
        $pxPorMin = self::PX_POR_HORA / 60;

        $resultado = [];

        foreach ($dias as $dia) {
            $eventos = $turnos->filter(fn ($t) => $t->fecha->toDateString() === $dia['fecha'])
                ->values()
                ->map(function ($t) use ($inicioMin, $pxPorMin) {
                    $inicioTurnoMin = $this->minutosDesdeMedianoche($t->hora_inicio);
                    $finTurnoMin = $this->minutosDesdeMedianoche($t->hora_fin);

                    return [
                        'turno_id' => $t->id,
                        'top' => max(0, ($inicioTurnoMin - $inicioMin) * $pxPorMin),
                        'alto' => max(18, ($finTurnoMin - $inicioTurnoMin) * $pxPorMin),
                        'lane' => 0,
                        'lanes' => 1,
                        'espacio' => $t->espacio->nombre,
                        'horario' => substr($t->hora_inicio, 0, 5) . ' - ' . substr($t->hora_fin, 0, 5),
                        'estado' => $t->estado->value,
                    ];
                })
                ->all();

            $resultado[$dia['fecha']] = $this->asignarCarriles($eventos);
        }

        return $resultado;
    }

    protected function minutosDesdeMedianoche(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));

        return $h * 60 + $m;
    }

    /**
     * Igual que en el calendario de reserva: reparte en carriles los
     * eventos que se solapan en el tiempo, para que se vean lado a lado en
     * vez de superpuestos, usando posición/alto en píxeles en vez de
     * índices de fila.
     *
     * @param  array<int, array{top: float, alto: float}>  $eventos
     * @return array<int, array{top: float, alto: float, lane: int, lanes: int}>
     */
    protected function asignarCarriles(array $eventos): array
    {
        if (count($eventos) <= 1) {
            return $eventos;
        }

        usort($eventos, fn ($a, $b) => $a['top'] <=> $b['top']);

        $seSolapan = fn (array $a, array $b) => $a['top'] < $b['top'] + $b['alto'] && $a['top'] + $a['alto'] > $b['top'];

        $finPorCarril = [];
        foreach ($eventos as &$evento) {
            $asignado = false;

            foreach ($finPorCarril as $carril => $topFinOcupado) {
                if ($evento['top'] >= $topFinOcupado) {
                    $evento['lane'] = $carril;
                    $finPorCarril[$carril] = $evento['top'] + $evento['alto'];
                    $asignado = true;
                    break;
                }
            }

            if (!$asignado) {
                $evento['lane'] = count($finPorCarril);
                $finPorCarril[] = $evento['top'] + $evento['alto'];
            }
        }
        unset($evento);

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

    public function render()
    {
        $dias = $this->diasSemana();
        $turnos = $this->turnosSemana($dias);
        $eventosPorDia = $this->eventosPorDia($dias, $turnos);

        $turnoModalId = $this->turnoModalRecepcion ?? $this->turnoModalEntrega;
        $turnoEnModal = $turnoModalId ? Turno::with('espacio')->find($turnoModalId) : null;
        $turnoEnDetalle = $this->turnoDetalle ? Turno::with(['espacio', 'carrera'])->find($this->turnoDetalle) : null;

        return view('livewire.mis-turnos', [
            'dias' => $dias,
            'eventosPorDia' => $eventosPorDia,
            'horas' => range(self::HORA_INICIO_CALENDARIO, self::HORA_FIN_CALENDARIO - 1),
            'altoTotal' => (self::HORA_FIN_CALENDARIO - self::HORA_INICIO_CALENDARIO) * self::PX_POR_HORA,
            'rangoSemana' => Carbon::parse($dias[0]['fecha'])->format('d/m') . ' – ' . Carbon::parse($dias[6]['fecha'])->format('d/m/Y'),
            'turnoEnModal' => $turnoEnModal,
            'turnoEnDetalle' => $turnoEnDetalle,
        ]);
    }
}
