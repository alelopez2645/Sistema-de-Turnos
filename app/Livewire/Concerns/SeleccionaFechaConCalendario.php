<?php

namespace App\Livewire\Concerns;

use App\Models\Espacio;
use Illuminate\Support\Carbon;

/**
 * Lógica compartida para reemplazar el <input type="date"> por un calendario
 * visual donde los días no habilitados por administración (según los días de
 * la semana configurados en HorarioDisponibilidad) quedan bloqueados, además
 * de los días que ya pasaron.
 *
 * Los componentes que usan este trait deben tener una propiedad pública
 * `$fecha`, un método `updatedFecha()` (el hook normal de Livewire, que se
 * invoca manualmente al elegir un día del calendario) e implementar
 * `espacioParaCalendario()`.
 */
trait SeleccionaFechaConCalendario
{
    /** Mes que se está mostrando en el calendario, formato 'Y-m'. */
    public string $mesCalendario = '';

    abstract protected function espacioParaCalendario(): Espacio;

    /**
     * Debe llamarse desde mount(), una vez que el espacio (o turno) ya esté
     * disponible, para dejar el calendario abierto en el mes de la fecha
     * actual (si ya hay una elegida) o en el mes en curso.
     */
    protected function inicializarCalendario(): void
    {
        $this->mesCalendario = ($this->fecha ? Carbon::parse($this->fecha) : now())->format('Y-m');
    }

    public function mesAnterior(): void
    {
        $this->mesCalendario = Carbon::parse($this->mesCalendario . '-01')->subMonthNoOverflow()->format('Y-m');
    }

    public function mesSiguiente(): void
    {
        $this->mesCalendario = Carbon::parse($this->mesCalendario . '-01')->addMonthNoOverflow()->format('Y-m');
    }

    /**
     * Nombre del mes mostrado, en español, sin depender del locale configurado
     * en la app (para no atarse a que APP_LOCALE esté en 'es').
     */
    public function nombreMesCalendario(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $fecha = Carbon::parse($this->mesCalendario . '-01');

        return $meses[$fecha->month] . ' ' . $fecha->year;
    }

    /**
     * Se dispara al hacer click en un día del calendario. Si el día está
     * bloqueado no hace nada (además de que en la vista el botón ya está
     * deshabilitado, esto es un resguardo extra del lado del servidor).
     */
    public function seleccionarFecha(string $fecha): void
    {
        if (in_array($fecha, $this->fechasDeshabilitadas(), true)) {
            return;
        }

        $this->fecha = $fecha;
        $this->updatedFecha();
    }

    /**
     * Días de la semana (0 = domingo .. 6 = sábado) que tienen al menos una
     * franja horaria activa habilitada por administración para el espacio.
     */
    protected function diasSemanaHabilitados(): array
    {
        return $this->espacioParaCalendario()
            ->horariosDisponibilidad()
            ->where('activo', true)
            ->pluck('dia_semana')
            ->unique()
            ->all();
    }

    /**
     * Arma las celdas a renderizar del mes mostrado en $mesCalendario,
     * completando con días de los meses adyacentes para tener semanas
     * completas (de lunes a domingo).
     *
     * @return array<int, array{fecha: string, dia: int, delMes: bool, esHoy: bool, esSeleccionado: bool, deshabilitado: bool}>
     */
    public function diasCalendario(): array
    {
        $inicioMes = Carbon::parse($this->mesCalendario . '-01');
        $finMes = $inicioMes->copy()->endOfMonth();

        $inicioGrilla = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $finGrilla = $finMes->copy()->endOfWeek(Carbon::SUNDAY);

        $diasHabilitados = $this->diasSemanaHabilitados();
        $hoy = now()->toDateString();

        $dias = [];
        $cursor = $inicioGrilla->copy();

        while ($cursor->lte($finGrilla)) {
            $fechaStr = $cursor->toDateString();

            $dias[] = [
                'fecha' => $fechaStr,
                'dia' => $cursor->day,
                'delMes' => $cursor->month === $inicioMes->month,
                'esHoy' => $fechaStr === $hoy,
                'esSeleccionado' => $fechaStr === $this->fecha,
                'deshabilitado' => $fechaStr < $hoy || !in_array($cursor->dayOfWeek, $diasHabilitados, true),
            ];

            $cursor->addDay();
        }

        return $dias;
    }

    protected function fechasDeshabilitadas(): array
    {
        return collect($this->diasCalendario())
            ->where('deshabilitado', true)
            ->pluck('fecha')
            ->all();
    }
}
