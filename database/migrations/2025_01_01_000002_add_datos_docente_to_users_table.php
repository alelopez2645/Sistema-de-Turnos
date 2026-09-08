<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NOTA: esta migración asume que ya existe la tabla `users` por defecto de Laravel.
// Si arrancás un proyecto nuevo, corré esta migración DESPUÉS de la migración
// original de `users` (0001_01_01_000000_create_users_table).

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('docente')->after('password'); // docente | administrador
            $table->string('dni')->nullable()->unique()->after('rol');
            $table->string('telefono')->nullable()->after('dni');
            $table->foreignId('carrera_id')
                ->nullable()
                ->after('telefono')
                ->constrained('carreras')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carrera_id');
            $table->dropColumn(['rol', 'dni', 'telefono']);
        });
    }
};
