<?php

namespace App\Livewire;

use App\Enums\EstadoTurno;
use App\Enums\TipoEspacio;
use App\Models\Espacio;
use App\Models\Turno;
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

    protected function turnosBase()
    {
        $query = Turno::query();

        return $this->alcance === 'aprobado'
            ? $query->where('estado', EstadoTurno::APROBADO->value)
            : $query->where('estado', '!=', EstadoTurno::CANCELADO->value);
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

        $porEspacio = (clone $this->turnosBase())
            ->join('espacios', 'espacios.id', '=', 'turnos.espacio_id')
            ->selectRaw('espacios.nombre as espacio, count(*) as cantidad')
            ->groupBy('espacios.nombre')
            ->orderByDesc('cantidad')
            ->get();

        $maxEspacio = (int) ($porEspacio->max('cantidad') ?: 1);

        $porCarreraEspacio = (clone $this->turnosBase())
            ->join('espacios', 'espacios.id', '=', 'turnos.espacio_id')
            ->leftJoin('carreras', 'carreras.id', '=', 'turnos.carrera_id')
            ->selectRaw("coalesce(carreras.nombre, 'Sin carrera') as carrera, espacios.nombre as espacio, count(*) as cantidad")
            ->groupBy('carreras.nombre', 'espacios.nombre')
            ->orderByDesc('cantidad')
            ->limit(10)
            ->get();

        $porHora = (clone $this->turnosBase())
            ->selectRaw('substr(hora_inicio, 1, 2) as hora, count(*) as cantidad')
            ->groupBy('hora')
            ->orderBy('hora')
            ->get();

        $maxPorHora = (int) ($porHora->max('cantidad') ?: 1);

        $diasLabel = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];

        $conteoPorDia = (clone $this->turnosBase())
            ->get(['fecha'])
            ->groupBy(fn ($t) => $t->fecha->dayOfWeek)
            ->map->count();

        $porDia = collect($diasLabel)->map(fn ($nombre, $numero) => [
            'dia' => $nombre,
            'cantidad' => $conteoPorDia[$numero] ?? 0,
        ])->values();

        $maxPorDia = (int) ($porDia->max('cantidad') ?: 1);

        $espacioInformatica = Espacio::where('tipo', TipoEspacio::SALA_INFORMATICA->value)->first();

        $promedioComputadoras = $espacioInformatica
            ? (clone $this->turnosBase())->where('espacio_id', $espacioInformatica->id)->avg('cantidad_asistentes_aproximada')
            : null;

        // Frases en lenguaje simple que resumen los datos, para que el
        // dashboard se entienda de un vistazo sin tener que leer cada gráfico.
        $insights = [];

        if ($porEspacio->isNotEmpty()) {
            $top = $porEspacio->first();
            $insights[] = "El espacio más solicitado es {$top->espacio}, con {$top->cantidad} turnos.";
        }

        if ($porHora->isNotEmpty()) {
            $top = $porHora->sortByDesc('cantidad')->first();
            $insights[] = "La franja con más demanda arranca a las {$top->hora}:00, con {$top->cantidad} turnos.";
        }

        $diaTop = collect($porDia)->sortByDesc('cantidad')->first();
        if ($diaTop && $diaTop['cantidad'] > 0) {
            $insights[] = "El día con más reservas es {$diaTop['dia']}, con {$diaTop['cantidad']} turnos.";
        }

        return view('livewire.admin-dashboard', [
            'totales' => $totales,
            'porEspacio' => $porEspacio,
            'maxEspacio' => $maxEspacio,
            'porCarreraEspacio' => $porCarreraEspacio,
            'porHora' => $porHora,
            'maxPorHora' => $maxPorHora,
            'porDia' => $porDia,
            'maxPorDia' => $maxPorDia,
            'promedioComputadoras' => $promedioComputadoras,
            'insights' => $insights,
        ]);
    }
}
