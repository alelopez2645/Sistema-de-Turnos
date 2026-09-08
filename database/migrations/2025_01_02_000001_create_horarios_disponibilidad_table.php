<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_disponibilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('espacio_id')->constrained('espacios')->cascadeOnDelete();

            // 0 = domingo ... 6 = sábado (mismo criterio que Carbon::dayOfWeek)
            $table->unsignedTinyInteger('dia_semana');

            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index(['espacio_id', 'dia_semana', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_disponibilidad');
    }
};
