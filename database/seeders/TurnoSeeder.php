<?php

namespace Database\Seeders;

use App\Enums\EstadoTurno;
use App\Enums\RolUsuario;
use App\Models\Carrera;
use App\Models\Espacio;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Genera turnos de ejemplo para todo el mes actual (que incluye "hoy" y
 * "esta semana"), repartidos entre los distintos espacios, con una mezcla
 * realista de estados: los días pasados quedan mayormente aprobados (varios
 * con recepción/entrega ya registradas, simulando uso real), y los días
 * futuros quedan como pendientes/aprobados a la espera de la fecha.
 *
 * OJO: no se ejecuta desde DatabaseSeeder porque es data de prueba, no parte
 * de un alta limpia del sistema. Se corre a mano cuando hace falta:
 *   php artisan db:seed --class=TurnoSeeder
 * Es idempotente: cada corrida borra los turnos existentes y genera un lote
 * nuevo, para no ir acumulando duplicados en corridas sucesivas.
 *
 * No se admiten reservas en sábado ni domingo (regla del sistema), así que
 * si "hoy" cae en fin de semana simplemente no va a haber turnos para hoy.
 */
class TurnoSeeder extends Seeder
{
    protected array $motivos = [
        'Clase especial con invitados',
        'Reunión de equipo docente',
        'Charla informativa para estudiantes',
        'Evaluación integradora',
        'Taller práctico',
        'Presentación de proyectos finales',
        'Jornada de bienvenida a ingresantes',
        'Capacitación interna',
        'Clase de repaso previa a examen',
        'Actividad extracurricular',
    ];

    public function run(): void
    {
        Turno::query()->delete();

        $docentes = $this->docentesDeEjemplo();
        $carreras = Carrera::all();
        $espacios = Espacio::all();

        if ($docentes->isEmpty() || $carreras->isEmpty() || $espacios->isEmpty()) {
            $this->command?->warn('Faltan docentes, carreras o espacios. Corré CarreraSeeder, EspacioSeeder, HorarioDisponibilidadSeeder y UserSeeder antes que este.');

            return;
        }

        $hoy = Carbon::today();
        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();

        // Ocupación en memoria por espacio+fecha, para no generar más
        // solapamientos de los que el propio espacio permite
        // (unidades_disponibles), igual que hace la app en vivo.
        $ocupacion = [];
        $creados = 0;

        for ($fecha = $inicioMes->copy(); $fecha->lte($finMes); $fecha->addDay()) {
            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true)) {
                continue;
            }

            $esPasado = $fecha->lt($hoy);
            $esHoy = $fecha->isSameDay($hoy);

            foreach (range(1, random_int(2, 5)) as $intento) {
                $espacio = $espacios->random();
                $paquetes = collect($espacio->paquetesDisponibles($fecha->toDateString()));

                if ($paquetes->isEmpty()) {
                    continue;
                }

                $paquete = $paquetes->random();
                $ventana = $paquete['ventana'];
                $paquetesVentana = $paquetes->where('ventana', $ventana)->values();
                $indiceInicio = $paquetesVentana->search(fn ($p) => $p['inicio'] === $paquete['inicio']);
                $largo = random_int(1, min(2, $paquetesVentana->count() - $indiceInicio));

                $horaInicio = $paquetesVentana[$indiceInicio]['inicio'];
                $horaFin = $paquetesVentana[$indiceInicio + $largo - 1]['fin'];

                $ocupados = collect($ocupacion[$espacio->id][$fecha->toDateString()] ?? [])
                    ->filter(fn ($o) => $o['inicio'] < $horaFin && $o['fin'] > $horaInicio)
                    ->count();

                if ($ocupados >= $espacio->unidades_disponibles) {
                    continue;
                }

                $ocupacion[$espacio->id][$fecha->toDateString()][] = ['inicio' => $horaInicio, 'fin' => $horaFin];

                $tipo = $espacio->tipo;
                $estado = $this->elegirEstado($esPasado, $esHoy, $fecha, $horaFin);

                $capacidad = $espacio->capacidad ?? 30;
                $minAsistentes = max(1, (int) ($capacidad * 0.2));
                $maxAsistentes = max($minAsistentes, (int) ($capacidad * 0.9));

                $datos = [
                    'espacio_id' => $espacio->id,
                    'docente_id' => $docentes->random()->id,
                    'carrera_id' => $carreras->random()->id,
                    'fecha' => $fecha->toDateString(),
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'motivo' => $this->motivos[array_rand($this->motivos)],
                    'cantidad_asistentes_aproximada' => $tipo->requiereCantidadAsistentes()
                        ? random_int($minAsistentes, $maxAsistentes)
                        : null,
                    'curso' => Turno::CURSOS[array_rand(Turno::CURSOS)],
                    'estado' => $estado->value,
                    'terminos_aceptados' => true,
                    'terminos_aceptados_en' => $fecha->copy()->subDays(random_int(2, 5)),
                    'terminos_version' => config('reglas_uso.version'),
                ];

                if ($estado === EstadoTurno::RECHAZADO) {
                    $datos['observaciones'] = 'No hay disponibilidad de personal de apoyo para ese horario.';
                } elseif ($estado === EstadoTurno::APROBADO && random_int(0, 4) === 0) {
                    $datos['observaciones'] = 'Confirmado, recordar dejar el espacio en orden.';
                }

                $yaOcurrio = Carbon::parse($fecha->toDateString() . ' ' . $horaFin)->lt(now());

                if ($estado === EstadoTurno::APROBADO && $yaOcurrio && random_int(0, 9) > 1) {
                    $datos['recepcionado_en'] = Carbon::parse($fecha->toDateString() . ' ' . $horaInicio);
                    $datos['observaciones_recepcion'] = 'Todo en orden al momento de recibir el espacio.';
                    $datos['entregado_en'] = Carbon::parse($fecha->toDateString() . ' ' . $horaFin);
                    $datos['observaciones_entrega'] = 'Se devolvió en las mismas condiciones en que se recibió.';
                }

                Turno::create($datos);
                $creados++;
            }
        }

        $this->command?->info("TurnoSeeder: {$creados} turnos generados entre {$inicioMes->format('d/m')} y {$finMes->format('d/m')}.");
    }

    /**
     * Estado plausible según si la fecha ya pasó, es hoy, o todavía no llegó.
     */
    protected function elegirEstado(bool $esPasado, bool $esHoy, Carbon $fecha, string $horaFin): EstadoTurno
    {
        $azar = random_int(1, 100);

        if ($esPasado) {
            return match (true) {
                $azar <= 75 => EstadoTurno::APROBADO,
                $azar <= 90 => EstadoTurno::RECHAZADO,
                default => EstadoTurno::CANCELADO,
            };
        }

        if ($esHoy) {
            $yaOcurrio = Carbon::parse($fecha->toDateString() . ' ' . $horaFin)->lt(now());

            if ($yaOcurrio) {
                return $azar <= 85 ? EstadoTurno::APROBADO : EstadoTurno::RECHAZADO;
            }

            return match (true) {
                $azar <= 60 => EstadoTurno::APROBADO,
                $azar <= 90 => EstadoTurno::PENDIENTE,
                default => EstadoTurno::CANCELADO,
            };
        }

        // Días futuros del mes.
        return match (true) {
            $azar <= 45 => EstadoTurno::PENDIENTE,
            $azar <= 90 => EstadoTurno::APROBADO,
            default => EstadoTurno::CANCELADO,
        };
    }

    /**
     * Usa los docentes que ya existan; si hay pocos, agrega algunos de
     * ejemplo (repartidos en distintas carreras) para que los turnos de
     * prueba no queden todos a nombre de la misma persona.
     */
    protected function docentesDeEjemplo()
    {
        $docentes = User::where('rol', RolUsuario::DOCENTE->value)->where('activo', true)->get();

        if ($docentes->count() >= 5) {
            return $docentes;
        }

        $carreras = Carrera::inRandomOrder()->take(6)->get();

        $ejemplo = [
            ['name' => 'Verónica Chocobar', 'email' => 'veronica.chocobar@iesnuevohorizonte.com'],
            ['name' => 'Diego Ferreyra', 'email' => 'diego.ferreyra@iesnuevohorizonte.com'],
            ['name' => 'Lucía Cardozo', 'email' => 'lucia.cardozo@iesnuevohorizonte.com'],
            ['name' => 'Martín Quipildor', 'email' => 'martin.quipildor@iesnuevohorizonte.com'],
            ['name' => 'Romina Aramayo', 'email' => 'romina.aramayo@iesnuevohorizonte.com'],
        ];

        foreach ($ejemplo as $i => $datos) {
            User::firstOrCreate(
                ['email' => $datos['email']],
                [
                    'name' => $datos['name'],
                    'password' => Hash::make('Docente123!'),
                    'rol' => RolUsuario::DOCENTE->value,
                    'carrera_id' => $carreras->isNotEmpty() ? $carreras->get($i % $carreras->count())?->id : null,
                    'activo' => true,
                ]
            );
        }

        return User::where('rol', RolUsuario::DOCENTE->value)->where('activo', true)->get();
    }
}
