<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            // TV Smart y Proyector ya no piden este dato (ver
            // TipoEspacio::requiereCantidadAsistentes()), así que queda nulo
            // para esos turnos en vez de forzar un valor inventado.
            $table->unsignedInteger('cantidad_asistentes_aproximada')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_asistentes_aproximada')->nullable(false)->change();
        });
    }
};
