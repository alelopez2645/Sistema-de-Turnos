<?php

namespace Database\Seeders;

use App\Enums\TipoEspacio;
use App\Models\Espacio;
use Illuminate\Database\Seeder;

class EspacioSeeder extends Seeder
{
    public function run(): void
    {
        Espacio::updateOrCreate(
            ['tipo' => TipoEspacio::AUDITORIO->value],
            [
                'nombre' => 'Auditorio',
                'capacidad' => 200,
                'unidades_disponibles' => 1,
                'equipamiento' => 'sillas, escenario, equipo de audio, micrófonos y pantalla',
            ]
        );

        Espacio::updateOrCreate(
            ['tipo' => TipoEspacio::SALA_INFORMATICA->value],
            [
                'nombre' => 'Sala de Informática',
                'capacidad' => 32,
                'unidades_disponibles' => 1,
                'equipamiento' => '32 PCs de escritorio',
            ]
        );

        Espacio::updateOrCreate(
            ['tipo' => TipoEspacio::SALA_CAPACITACION->value],
            [
                'nombre' => 'Sala de Capacitación',
                'capacidad' => 80,
                'unidades_disponibles' => 1,
                'equipamiento' => 'sillas',
            ]
        );

        Espacio::updateOrCreate(
            ['tipo' => TipoEspacio::TV_SMART->value],
            [
                'nombre' => 'TV Smart',
                // Sin capacidad: no aplica el concepto de "personas" a este recurso.
                'capacidad' => null,
                // TODO: confirmar con administración la cantidad real de televisores disponibles
                'unidades_disponibles' => 5,
                'equipamiento' => 'Televisor smart con entrada HDMI',
            ]
        );

        Espacio::updateOrCreate(
            ['tipo' => TipoEspacio::PROYECTOR->value],
            [
                'nombre' => 'Proyector',
                // Sin capacidad: no aplica el concepto de "personas" a este recurso.
                'capacidad' => null,
                // TODO: confirmar con administración la cantidad real de proyectores disponibles
                'unidades_disponibles' => 3,
                'equipamiento' => 'Proyector portátil con entrada HDMI',
            ]
        );
    }
}
