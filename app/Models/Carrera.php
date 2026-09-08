<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    public function docentes(): HasMany
    {
        return $this->hasMany(User::class, 'carrera_id');
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }
}
