<?php

namespace App\Enums;

enum TipoEspacio: string
{
    case AUDITORIO = 'auditorio';
    case SALA_INFORMATICA = 'sala_informatica';
    case SALA_CAPACITACION = 'sala_capacitacion';
    case TV_SMART = 'tv_smart';
    case PROYECTOR = 'proyector';

    public function label(): string
    {
        return match ($this) {
            self::AUDITORIO => 'Auditorio',
            self::SALA_INFORMATICA => 'Sala de Informática',
            self::SALA_CAPACITACION => 'Sala de Capacitación',
            self::TV_SMART => 'TV Smart',
            self::PROYECTOR => 'Proyector',
        };
    }

    /**
     * Auditorio y Sala de Capacitación piden, además, una nota formal
     * (imagen) del trámite administrativo antes de confirmar la reserva.
     */
    public function requiereNotaFormal(): bool
    {
        return in_array($this, [self::AUDITORIO, self::SALA_CAPACITACION], true);
    }

    /**
     * Todos los espacios arman el horario en paquetes de duración fija
     * dentro de las franjas habilitadas por administración (ver
     * intervaloMinutos()). TV Smart y Proyector usan la misma lógica que
     * Sala de Informática (paquetes de 40 minutos), ya que se prestan para
     * usar durante un módulo de clase.
     */
    public function usaPaquetesHorarios(): bool
    {
        return true;
    }

    /**
     * Duración, en minutos, de cada paquete horario.
     */
    public function intervaloMinutos(): int
    {
        return match ($this) {
            self::AUDITORIO, self::SALA_CAPACITACION => 30,
            self::SALA_INFORMATICA, self::TV_SMART, self::PROYECTOR => 40,
        };
    }

    /**
     * Auditorio y Sala de Capacitación necesitan aprobación de administración
     * antes de quedar confirmados. Sala de Informática, TV Smart y Proyector
     * se reservan directamente (quedan en estado "aprobado" desde el
     * momento en que se crean).
     */
    public function requiereAprobacion(): bool
    {
        return in_array($this, [self::AUDITORIO, self::SALA_CAPACITACION], true);
    }

    /**
     * Horas mínimas de anticipación exigidas para reservar este tipo de
     * espacio. Sala de Informática, TV Smart y Proyector no exigen
     * anticipación mínima (más allá de no poder reservar en una fecha/hora
     * ya pasada).
     */
    public function horasAnticipacionMinima(): int
    {
        return match ($this) {
            self::SALA_INFORMATICA, self::TV_SMART, self::PROYECTOR => 0,
            default => 48,
        };
    }

    /**
     * TV Smart y Proyector son recursos móviles que se prestan para usar
     * dentro de un aula que ya tiene su propio curso a cargo: no tiene
     * sentido pedir una cantidad de asistentes para el préstamo en sí.
     */
    public function requiereCantidadAsistentes(): bool
    {
        return !in_array($this, [self::TV_SMART, self::PROYECTOR], true);
    }
}
