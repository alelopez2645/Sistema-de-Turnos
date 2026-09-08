<?php

namespace Database\Seeders;

use App\Enums\TipoEspacio;
use App\Models\Espacio;
use App\Models\HorarioDisponibilidad;
use Illuminate\Database\Seeder;

class HorarioDisponibilidadSeeder extends Seeder
{
    /**
     * Ventanas horarias por defecto, lunes a viernes. Administración puede
     * agregar, desactivar o eliminar franjas desde /admin/disponibilidad.
     *
     * Sala de Informática, TV Smart y Proyector usan 2 módulos con un corte
     * al mediodía: 08:00–12:40 (7 paquetes de 40 min) y 13:20–21:00
     * (11 paquetes de 40 min, quedan 20 min sin usar al final de la tarde ya
     * que 7h40 no es múltiplo exacto de 40 minutos). Auditorio y Sala de
     * Capacitación usan una única franja corrida de 08:00 a 21:00.
     */
    public function run(): void
    {
        $tiposConDobleModulo = [
            TipoEspacio::SALA_INFORMATICA,
            TipoEspacio::TV_SMART,
            TipoEspacio::PROYECTOR,
        ];

        foreach (Espacio::all() as $espacio) {
            $ventanas = in_array($espacio->tipo, $tiposConDobleModulo, true)
                ? [['08:00:00', '12:40:00'], ['13:20:00', '21:00:00']]
                : [['08:00:00', '21:00:00']];

            foreach (range(1, 5) as $diaSemana) { // 1 = lunes ... 5 = viernes
                foreach ($ventanas as [$inicio, $fin]) {
                    HorarioDisponibilidad::updateOrCreate(
                        [
                            'espacio_id' => $espacio->id,
                            'dia_semana' => $diaSemana,
                            'hora_inicio' => $inicio,
                        ],
                        [
                            'hora_fin' => $fin,
                            'activo' => true,
                        ]
                    );
                }
            }
        }
    }
}
