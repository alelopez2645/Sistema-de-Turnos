<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            // Ruta, en el disco "public", de la imagen de la nota formal del
            // trámite administrativo. Solo se completa para Auditorio y Sala
            // de Capacitación (ver TipoEspacio::requiereNotaFormal()).
            $table->string('nota_formal_path')->nullable()->after('curso');
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn('nota_formal_path');
        });
    }
};
