<?php

/**
 * Reglas de uso mostradas en el popup de condiciones que el docente debe
 * aceptar antes de confirmar una reserva.
 *
 * - 'items': reglas genéricas, usadas para los espacios que no tienen un
 *   texto propio en 'por_tipo' (hoy: Sala de Informática, TV Smart, Proyector).
 * - 'por_tipo': reglas específicas por tipo de espacio (value de TipoEspacio).
 *   OJO: a esta lista, FormularioTurno le antepone automáticamente una regla
 *   con la capacidad y el equipamiento reales del espacio (tomados de
 *   espacios.capacidad / espacios.equipamiento), así que no hace falta
 *   repetir ese dato acá — si administración cambia el equipamiento desde
 *   /admin/espacios, el popup se actualiza solo.
 *
 * Si el contenido cambia de forma sustancial, conviene subir "version" para
 * poder distinguir, en el histórico de turnos, con qué versión de las reglas
 * aceptó cada docente (queda guardado en turnos.terminos_version).
 */
return [
    'version' => '3.0',

    'items' => [
        'No se permite comer ni beber dentro del espacio ni cerca del equipamiento.',
        'Quien reserva es responsable del cuidado del mobiliario, los equipos y las instalaciones durante todo el uso.',
        'Cualquier daño o desperfecto debe informarse de inmediato a administración.',
        'El espacio o equipo debe entregarse en las mismas condiciones en que fue recibido.',
        'El horario reservado debe respetarse; si finalmente no vas a usar el turno, cancelalo con anticipación para liberarlo.',
    ],

    'por_tipo' => [
        'auditorio' => [
            'El docente debe disponer de una computadora y cable HDMI para el uso del equipo tecnológico.',
            'El docente debe responsabilizarse de la sala y sus recursos en el inicio y fin del evento.',
            'Para cada evento, el docente responsable debe hacer uso de los recursos institucionales (banners, manteles, logos) que deberá solicitar al equipo pedagógico.',
        ],

        'sala_capacitacion' => [
            'El docente debe responsabilizarse de la sala y sus recursos en el inicio y fin del evento.',
            'Para cada evento, el docente responsable debe hacer uso de los recursos institucionales (banners, manteles, logos) que deberá solicitar al equipo pedagógico.',
        ],
    ],
];
