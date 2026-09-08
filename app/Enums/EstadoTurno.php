<?php

namespace App\Enums;

enum EstadoTurno: string
{
    case PENDIENTE = 'pendiente';
    case APROBADO = 'aprobado';
    case RECHAZADO = 'rechazado';
    case CANCELADO = 'cancelado';
}
