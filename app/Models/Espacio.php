<?php

namespace App\Models;

use App\Enums\TipoEspacio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Espacio extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'tipo', 'capacidad', 'unidades_disponibles', 'equipamiento'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoEspacio::class,
            'capacidad' => 'integer',
            'unidades_disponibles' => 'integer',
        ];
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }

    public function horariosDisponibilidad(): HasMany
    {
        return $this->hasMany(HorarioDisponibilidad::class);
    }

    /**
     * Determina si el rango horario solicitado está completamente contenido
     * dentro de alguna franja habilitada por administración para ese día
     * de la semana. Si no hay ninguna franja activa que lo cubra, no está disponible.
     */
    public function disponibleEn(string $fecha, string $horaInicio, string $horaFin): bool
    {
        $diaSemana = Carbon::parse($fecha)->dayOfWeek;

        return $this->horariosDisponibilidad()
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->where('hora_inicio', '<=', $horaInicio)
            ->where('hora_fin', '>=', $horaFin)
            ->exists();
    }

    /**
     * Arma los paquetes horarios de duración fija (según
     * TipoEspacio::intervaloMinutos()) dentro de las franjas habilitadas por
     * administración para el día de la semana que corresponde a $fecha.
     * Solo tiene sentido para los tipos con usaPaquetesHorarios() === true.
     *
     * Devuelve un array de ['inicio' => 'HH:MM', 'fin' => 'HH:MM', 'ventana' => 'HH:MM:SS-HH:MM:SS'],
     * donde 'ventana' identifica a qué franja pertenece cada paquete (para no
     * permitir combinar paquetes de dos franjas separadas por un corte, como
     * el mediodía en la Sala de Informática).
     */
    public function paquetesDisponibles(string $fecha): array
    {
        $intervalo = $this->tipo->intervaloMinutos();

        if (!$intervalo) {
            return [];
        }

        $diaSemana = Carbon::parse($fecha)->dayOfWeek;

        $ventanas = $this->horariosDisponibilidad()
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->orderBy('hora_inicio')
            ->get();

        $paquetes = [];

        foreach ($ventanas as $ventana) {
            $actual = Carbon::createFromFormat('H:i:s', $ventana->hora_inicio);
            $finVentana = Carbon::createFromFormat('H:i:s', $ventana->hora_fin);
            $claveVentana = $ventana->hora_inicio . '-' . $ventana->hora_fin;

            // Solo se arman paquetes completos: si sobra un resto menor al
            // intervalo al final de la franja, ese resto queda sin usar.
            while ($actual->copy()->addMinutes($intervalo)->lte($finVentana)) {
                $paquetes[] = [
                    'inicio' => $actual->format('H:i'),
                    'fin' => $actual->copy()->addMinutes($intervalo)->format('H:i'),
                    'ventana' => $claveVentana,
                ];
                $actual->addMinutes($intervalo);
            }
        }

        return $paquetes;
    }
}
