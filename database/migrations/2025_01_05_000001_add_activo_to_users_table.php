<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Usamos "baja" como desactivación en vez de borrado físico: el
            // FK de turnos.docente_id tiene cascadeOnDelete(), así que borrar
            // un usuario de verdad borraría también todo su historial de
            // turnos. Un usuario inactivo no puede iniciar sesión, pero sus
            // turnos pasados se conservan intactos.
            $table->boolean('activo')->default(true)->after('carrera_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
