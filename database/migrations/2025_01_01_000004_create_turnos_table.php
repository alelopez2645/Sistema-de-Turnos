<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('espacio_id')->constrained('espacios')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('carrera_id')->nullable()->constrained('carreras')->nullOnDelete();

            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->string('motivo');
            $table->unsignedInteger('cantidad_asistentes_aproximada');
            $table->string('curso')->nullable(); // obligatorio solo para sala_informatica

            $table->string('estado')->default('pendiente'); // pendiente|aprobado|rechazado|cancelado
            $table->text('observaciones')->nullable(); // notas del admin al aprobar/rechazar

            $table->timestamps();

            $table->index(['espacio_id', 'fecha']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
