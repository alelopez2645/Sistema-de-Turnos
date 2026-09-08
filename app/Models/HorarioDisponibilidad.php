<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioDisponibilidad extends Model
{
    use HasFactory;

    protected $table = 'horarios_disponibilidad';

    protected $fillable = [
        'espacio_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function espacio(): BelongsTo
    {
        return $this->belongsTo(Espacio::class);
    }
}
