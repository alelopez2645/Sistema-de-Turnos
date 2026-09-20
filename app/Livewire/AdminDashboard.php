<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Enums\TipoEspacio;
use App\Models\Espacio;
use App\Models\Turno;
use Illuminate\Support\Carbon;
use Livewire\Component;

class AdminDashboard extends Component
{
    /**
     * 'todos' considera toda solicitud que no haya sido cancelada (incluye
     * pendientes, que reflejan demanda aunque no se hayan confirmado).
     * 'aprobado' considera solo el uso real y confirmado de cada espacio.
     */
    public string $alcance = 'todos';

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdministrador(), 403);
    }

    /**
     * Cuando cambia el filtro "todos/aprobado", Livewire ya vuelve a
     * renderizar solo la vista (las barras de progreso, la tabla, etc. se
     * actualizan solas). Los gráficos de Chart.js viven en un
     * wire:ignore para no perder su estado de animación en cada
     * actualización, así que les avisamos por separado con los datos
     * frescos para que se redibujen.
     */
    public function updatedAlcance(): void
    {
        $this->dispatch('dashboard-actualizado', datos: $this->datosGraficos());
    }

    protected function turnosBase()
    {
        $query = Turno::query();

        return $this->alcance === 'aprobado'
            ? $query->where('estado', EstadoTurno::APROBADO->value)
            : $query->where('estado', '!=', EstadoTurno::CANCELADO->value);
    }

    /**
     * Arma, en arrays planos (aptos para JSON), todos los datos que
     * alimentan los gráficos. Se usa tanto en render() como al cambiar el
     * filtro de alcance (updatedAlcance), para que ambos caminos queden
     * siempre sincronizados.
     */
    protected function datosGraficos(): array
    {
        $porEspacio = (clone $this->turnosBase())
            ->join('espacios', 'espacios.id', '=', 'turnos.espacio_id')
            ->selectRaw('espacios.nombre as espacio, count(*) as cantidad')
            ->groupBy('espacios.nombre')
            ->orderByDesc('cantidad')
            ->get();

        $porHora = (clone $this->turnosBase())
            ->selectRaw('substr(hora_inicio, 1, 2) as hora, count(*) as cantidad')
            ->groupBy('hora')
            ->orderBy('hora')
            ->get();

        $diasLabel = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];

        $conteoPorDia = (clone $this->turnosBase())
            ->get(['fecha'])
            ->groupBy(fn ($t) => $t->fecha->dayOfWeek)
            ->map->count();

        $porDia = collect($diasLabel)->map(fn ($nombre, $numero) => [
            'dia' => $nombre,
            'cantidad' => $conteoPorDia[$numero] ?? 0,
        ])->values();

        $totales = [
            'pendientes' => $this->alcance === 'aprobado' ? 0 : Turno::where('estado', EstadoTurno::PENDIENTE->value)->count(),
            'aprobados' => Turno::where('estado', EstadoTurno::APROBADO->value)->count(),
            'rechazados' => $this->alcance === 'aprobado' ? 0 : Turno::where('estado', EstadoTurno::RECHAZADO->value)->count(),
            'cancelados' => 0,
        ];

        return [
            'porEspacio' => [
                'labels' => $porEspacio->pluck('espacio')->all(),
                'valores' => $porEspacio->pluck('cantidad')->all(),
            ],
            'porHora' => [
                'labels' => $porHora->pluck('hora')->map(fn ($h) => $h . ':00')->all(),
                'valores' => $porHora->pluck('cantidad')->all(),
            ],
            'porDia' => [
                'labels' => $porDia->pluck('dia')->all(),
                'valores' => $porDia->pluck('cantidad')->all(),
            ],
            'porEstado' => [
                'labels' => ['Pendientes', 'Aprobados', 'Rechazados'],
                'valores' => [$totales['pendientes'], $totales['aprobados'], $totales['rechazados']],
            ],
            'porSemana' => $this->porSemana(),
        ];
    }

    /**
     * Cantidad de turnos por semana (lunes a domingo) de las últimas 8
     * semanas, para mostrar la tendencia de uso a lo largo del tiempo.
     * Esta serie siempre mira TODAS las solicitudes activas (no se filtra
     * por "alcance"), porque lo que interesa acá es la demanda a lo largo
     * del tiempo, no si cada una ya fue aprobada.
     */
    protected function porSemana(): array
    {
        $inicioPrimeraSemana = now()->startOfWeek(Carbon::MONDAY)->subWeeks(7);

        $turnos = Turno::where('estado', '!=', EstadoTurno::CANCELADO->value)
            ->whereBetween('fecha', [$inicioPrimeraSemana->toDateString(), now()->endOfWeek(Carbon::SUNDAY)->toDateString()])
            ->get(['fecha']);

        $semanas = collect(range(0, 7))->map(function ($i) use ($inicioPrimeraSemana, $turnos) {
            $lunes = $inicioPrimeraSemana->copy()->addWeeks($i);
            $domingo = $lunes->copy()->endOfWeek(Carbon::SUNDAY);

            $cantidad = $turnos->filter(fn ($t) => $t->fecha->betweenIncluded($lunes, $domingo))->count();

            return ['etiqueta' => $lunes->format('d/m'), 'cantidad' => $cantidad];
        });

        return [
            'labels' => $semanas->pluck('etiqueta')->all(),
            'valores' => $semanas->pluck('cantidad')->all(),
        ];
    }

    public function render()
    {
        $totales = [
            'total' => Turno::count(),
            'pendientes' => Turno::where('estado', EstadoTurno::PENDIENTE->value)->count(),
            'aprobados' => Turno::where('estado', EstadoTurno::APROBADO->value)->count(),
            'rechazados' => Turno::where('estado', EstadoTurno::RECHAZADO->value)->count(),
            'cancelados' => Turno::where('estado', EstadoTurno::CANCELADO->value)->count(),
        ];

        $porCarreraEspacio = (clone $this->turnosBase())
            ->join('espacios', 'espacios.id', '=', 'turnos.espacio_id')
            ->leftJoin('carreras', 'carreras.id', '=', 'turnos.carrera_id')
            ->selectRaw("coalesce(carreras.nombre, 'Sin carrera') as carrera, espacios.nombre as espacio, count(*) as cantidad")
            ->groupBy('carreras.nombre', 'espacios.nombre')
            ->orderByDesc('cantidad')
            ->limit(10)
            ->get();

        $espacioInformatica = Espacio::where('tipo', TipoEspacio::SALA_INFORMATICA->value)->first();

        $promedioComputadoras = $espacioInformatica
            ? (clone $this->turnosBase())->where('espacio_id', $espacioInformatica->id)->avg('cantidad_asistentes_aproximada')
            : null;

        $datos = $this->datosGraficos();

        // Frases en lenguaje simple que resumen los datos, para que el
        // dashboard se entienda de un vistazo sin tener que leer cada gráfico.
        $insights = [];

        if (!empty($datos['porEspacio']['labels'])) {
            $idx = array_keys($datos['porEspacio']['valores'], max($datos['porEspacio']['valores']))[0];
            $insights[] = "El espacio más solicitado es {$datos['porEspacio']['labels'][$idx]}, con {$datos['porEspacio']['valores'][$idx]} turnos.";
        }

        if (!empty($datos['porHora']['labels'])) {
            $idx = array_keys($datos['porHora']['valores'], max($datos['porHora']['valores']))[0];
            $insights[] = "La franja con más demanda arranca a las {$datos['porHora']['labels'][$idx]}, con {$datos['porHora']['valores'][$idx]} turnos.";
        }

        $idxDia = array_keys($datos['porDia']['valores'], max($datos['porDia']['valores']))[0] ?? null;
        if ($idxDia !== null && $datos['porDia']['valores'][$idxDia] > 0) {
            $insights[] = "El día con más reservas es {$datos['porDia']['labels'][$idxDia]}, con {$datos['porDia']['valores'][$idxDia]} turnos.";
        }

        $semanas = $datos['porSemana']['valores'];
        if (count($semanas) >= 2 && end($semanas) !== null) {
            $ultima = $semanas[count($semanas) - 1];
            $anterior = $semanas[count($semanas) - 2];
            if ($anterior > 0) {
                $variacion = round((($ultima - $anterior) / $anterior) * 100);
                if ($variacion !== 0) {
                    $insights[] = 'Esta semana hubo ' . ($variacion > 0 ? 'un ' . $variacion . '% más' : 'un ' . abs($variacion) . '% menos') . ' de turnos que la semana anterior.';
                }
            }
        }

        return view('livewire.admin-dashboard', [
            'totales' => $totales,
            'porCarreraEspacio' => $porCarreraEspacio,
            'promedioComputadoras' => $promedioComputadoras,
            'insights' => $insights,
            'datosGraficos' => $datos,
        ]);
    }
}
