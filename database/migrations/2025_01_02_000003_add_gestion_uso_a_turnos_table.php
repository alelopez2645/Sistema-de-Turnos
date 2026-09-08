<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            // Aceptación del popup de condiciones de uso al momento de reservar.
            $table->boolean('terminos_aceptados')->default(false)->after('observaciones');
            $table->timestamp('terminos_aceptados_en')->nullable()->after('terminos_aceptados');
            $table->string('terminos_version', 20)->nullable()->after('terminos_aceptados_en');

            // Check-in: condiciones en las que el docente recibe el espacio/TV.
            $table->text('observaciones_recepcion')->nullable()->after('terminos_version');
            $table->timestamp('recepcionado_en')->nullable()->after('observaciones_recepcion');

            // Check-out: condiciones en las que el docente entrega el espacio/TV.
            $table->text('observaciones_entrega')->nullable()->after('recepcionado_en');
            $table->timestamp('entregado_en')->nullable()->after('observaciones_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn([
                'terminos_aceptados',
                'terminos_aceptados_en',
                'terminos_version',
                'observaciones_recepcion',
                'recepcionado_en',
                'observaciones_entrega',
                'entregado_en',
            ]);
        });
    }
};
