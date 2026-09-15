<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            // Etiqueta corta del tipo de encuentro (ej. "Muestra Presencial",
            // "Capacitación docente"), distinta del "motivo" (texto libre más
            // detallado). Nullable para no romper turnos ya existentes creados
            // antes de este campo.
            $table->string('tipo_reunion')->nullable()->after('carrera_id');
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn('tipo_reunion');
        });
    }
};
