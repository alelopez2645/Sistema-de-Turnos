<?php

namespace App\Models;

use App\Enums\EstadoTurno;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Turno extends Model
{
    use HasFactory;

    /**
     * Horas antes del inicio del turno hasta las que el docente puede modificarlo.
     */
    public const VENTANA_MODIFICACION_HORAS = 24;

    /**
     * Opciones fijas para el campo "curso", aplicable a la reserva de
     * cualquier espacio.
     */
    public const CURSOS = ['1er año', '2do año', '3er año'];

    protected $fillable = [
        'espacio_id',
        'docente_id',
        'carrera_id',
        'tipo_reunion',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'cantidad_asistentes_aproximada',
        'curso',
        'nota_formal_path',
        'estado',
        'observaciones',
        'terminos_aceptados',
        'terminos_aceptados_en',
        'terminos_version',
        'observaciones_recepcion',
        'imagenes_recepcion',
        'recepcionado_en',
        'observaciones_entrega',
        'imagenes_entrega',
        'entregado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoTurno::class,
            'cantidad_asistentes_aproximada' => 'integer',
            'terminos_aceptados' => 'boolean',
            'terminos_aceptados_en' => 'datetime',
            'recepcionado_en' => 'datetime',
            'entregado_en' => 'datetime',
            'imagenes_recepcion' => 'array',
            'imagenes_entrega' => 'array',
        ];
    }

    public function espacio(): BelongsTo
    {
        return $this->belongsTo(Espacio::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    /**
     * Cantidad de turnos pendientes o aprobados que se solapan en fecha y horario
     * para un espacio dado (excluyendo, opcionalmente, un turno puntual —
     * usado al editar un turno para no compararlo contra sí mismo).
     */
    public static function cantidadSolapada(
        int $espacioId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $ignorarTurnoId = null,
    ): int {
        return static::query()
            ->where('espacio_id', $espacioId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', [EstadoTurno::PENDIENTE->value, EstadoTurno::APROBADO->value])
            ->when($ignorarTurnoId, fn ($query) => $query->where('id', '!=', $ignorarTurnoId))
            ->where(function ($query) use ($horaInicio, $horaFin) {
                $query->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->count();
    }

    /**
     * Indica si todavía hay unidades disponibles del espacio (o televisores, en el
     * caso del TV Smart) para el rango horario solicitado. Para las salas físicas,
     * con unidades_disponibles = 1, equivale al viejo chequeo de "todo o nada".
     */
    public static function hayDisponibilidad(
        Espacio $espacio,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $ignorarTurnoId = null,
    ): bool {
        $solapados = static::cantidadSolapada($espacio->id, $fecha, $horaInicio, $horaFin, $ignorarTurnoId);

        return $solapados < $espacio->unidades_disponibles;
    }

    /**
     * Indica si la fecha/hora de inicio propuesta respeta la anticipación
     * mínima exigida (en horas). Al pasar 0, sigue exigiendo que la fecha/hora
     * de inicio no haya pasado ya respecto de este momento.
     */
    public static function cumpleAnticipacionMinima(string $fecha, string $horaInicio, int $horasMinimas): bool
    {
        $inicio = Carbon::parse($fecha . ' ' . $horaInicio);

        return $inicio->greaterThanOrEqualTo(now()->addHours($horasMinimas));
    }

    /**
     * Indica si la fecha dada cae en sábado o domingo (no habilitados para reservas).
     */
    public static function esFinDeSemana(string $fecha): bool
    {
        $dia = Carbon::parse($fecha)->dayOfWeek;

        return in_array($dia, [Carbon::SUNDAY, Carbon::SATURDAY], true);
    }

    /**
     * El docente puede modificar el turno mientras esté pendiente o aprobado
     * y falten más de VENTANA_MODIFICACION_HORAS para el inicio del evento.
     */
    public function puedeModificarse(): bool
    {
        if (!in_array($this->estado, [EstadoTurno::PENDIENTE, EstadoTurno::APROBADO], true)) {
            return false;
        }

        $inicio = Carbon::parse($this->fecha->format('Y-m-d') . ' ' . $this->hora_inicio);

        return now()->lessThan($inicio->clone()->subHours(self::VENTANA_MODIFICACION_HORAS));
    }

    /**
     * El turno puede recepcionarse (check-in) si está aprobado y todavía no se registró la recepción.
     */
    public function puedeRecepcionarse(): bool
    {
        return $this->estado === EstadoTurno::APROBADO && is_null($this->recepcionado_en);
    }

    /**
     * El turno puede entregarse (check-out) una vez recepcionado y mientras no se haya entregado ya.
     */
    public function puedeEntregarse(): bool
    {
        return !is_null($this->recepcionado_en) && is_null($this->entregado_en);
    }

    /**
     * URL pública de la imagen de la nota formal (requiere haber corrido
     * "php artisan storage:link" para que el disco "public" sea accesible
     * desde el navegador).
     */
    public function notaFormalUrl(): ?string
    {
        if (!$this->nota_formal_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->nota_formal_path);
    }

    /**
     * @return array<int, string>
     */
    public function imagenesRecepcionUrls(): array
    {
        return collect($this->imagenes_recepcion ?? [])
            ->map(fn ($path) => \Illuminate\Support\Facades\Storage::disk('public')->url($path))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function imagenesEntregaUrls(): array
    {
        return collect($this->imagenes_entrega ?? [])
            ->map(fn ($path) => \Illuminate\Support\Facades\Storage::disk('public')->url($path))
            ->all();
    }
}
