<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('espacios', function (Blueprint $table) {
            // Para las salas físicas vale 1 (una sola reserva por franja horaria).
            // Para el TV Smart representa la cantidad de televisores que administración
            // pone a disposición, permitiendo varias reservas simultáneas hasta ese tope.
            $table->unsignedInteger('unidades_disponibles')->default(1)->after('capacidad');
        });
    }

    public function down(): void
    {
        Schema::table('espacios', function (Blueprint $table) {
            $table->dropColumn('unidades_disponibles');
        });
    }
};
