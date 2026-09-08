<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('espacios', function (Blueprint $table) {
            // TV Smart y Proyector no manejan un concepto de "capacidad de
            // personas" propio del espacio, así que este campo pasa a ser
            // opcional (solo lo usan las salas físicas: Auditorio, Sala de
            // Informática y Sala de Capacitación).
            $table->unsignedInteger('capacidad')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('espacios', function (Blueprint $table) {
            $table->unsignedInteger('capacidad')->nullable(false)->change();
        });
    }
};
