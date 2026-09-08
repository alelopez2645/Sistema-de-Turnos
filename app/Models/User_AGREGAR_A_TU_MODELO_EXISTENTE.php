<?php

/**
 * Este archivo NO se copia tal cual. Es una referencia de lo que hay que
 * sumar a tu modelo App\Models\User (el que genera Laravel por defecto)
 * para que funcione con este módulo. No necesitás Sanctum ni HasApiTokens
 * para esta versión web (usa sesiones normales de Laravel).
 */

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User_AGREGAR_A_TU_MODELO_EXISTENTE
{
    // 1) Agregar a $fillable:
    // 'rol', 'dni', 'telefono', 'carrera_id'

    // 2) Agregar al método casts() (o a la propiedad $casts si usás Laravel < 11):
    // 'rol' => RolUsuario::class,

    // 3) Agregar estas relaciones y helpers:

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function turnosSolicitados(): HasMany
    {
        return $this->hasMany(Turno::class, 'docente_id');
    }

    public function esDocente(): bool
    {
        return $this->rol === RolUsuario::DOCENTE;
    }

    public function esAdministrador(): bool
    {
        return $this->rol === RolUsuario::ADMINISTRADOR;
    }
}
