<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            // Arrays de rutas (disco "public") de las imágenes opcionales
            // adjuntadas al registrar la recepción y la entrega del espacio/equipo.
            $table->json('imagenes_recepcion')->nullable()->after('observaciones_recepcion');
            $table->json('imagenes_entrega')->nullable()->after('observaciones_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn(['imagenes_recepcion', 'imagenes_entrega']);
        });
    }
};
