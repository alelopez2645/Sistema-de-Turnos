# Arreglo: horarios en columnas equivocadas y celdas "no disponible" sueltas

## Archivo modificado
- resources/views/livewire/formulario-turno.blade.php (reescrito por completo)

## Cómo aplicar
Reemplazá el archivo por este (completo, no un parche). No toca PHP, no
hace falta migración ni nada más.

## Cuál era la causa (las dos cosas que viste eran EL MISMO bug)
La grilla del calendario usa CSS Grid. Las celdas de horario (columna
izquierda) y las celdas de cada día se ubicaban en su lugar "automáticamente"
(dejando que el navegador las acomode en orden), mientras que los bloques
de reserva grandes (los que ahora ocupan varias filas con el contenido
adentro) se ubican con una posición **explícita** (le decimos exactamente
en qué fila/columna van, porque abarcan varias filas).

Cuando en una fila había un bloque de reserva explícito, el navegador
"reservaba" ese lugar primero y corría de lugar a las celdas automáticas de
esa fila — así terminaba un horario (como "15:00") apareciendo en la
columna del sábado en vez de la izquierda, y por el mismo corrimiento
aparecían celdas rayadas de "no disponible" en casillas de días hábiles que
en realidad estaban libres. Es el mismo efecto dominó, no dos bugs
distintos.

## El arreglo
Ahora **todas** las celdas (horario, cada día, y los bloques de reserva)
se ubican con posición explícita, calculada directamente desde los datos
(qué fila y qué columna les corresponde). Nada queda librado a que el
navegador "acomode" solo, así que no hay forma de que se corran.

De paso, antes solo se mostraba el número de hora en las filas "en punto"
(08:00, 09:00, etc.) y las filas de la media hora quedaban sin texto —
ahora TODAS las filas muestran su horario (08:00, 08:30, 09:00...), como
pediste.

## Cómo lo probé
No pude sacarte una captura de pantalla desde acá, así que en vez de
"mirar" el resultado verifiqué la posición de cada celda por código:
generé el calendario completo de Auditorio con reservas en distintos días
y confirmé, celda por celda, que:
- Las 26 celdas de horario (una por cada media hora entre 08:00 y 20:30)
  están TODAS en la columna 1, en filas consecutivas sin huecos ni
  repetidos.
- Cada día usa siempre la misma columna en las 26 filas (nunca se mezcla
  con la columna de otro día).
- Los bloques de reserva caen en la columna del día correcto.
- Las celdas "no disponible" (rayadas) aparecen únicamente en sábado y
  domingo — cero en los días hábiles, que es lo esperado porque el
  horario de Auditorio está habilitado de corrido de 08:00 a 21:00.
- Para Sala de Informática (que tiene el corte del mediodía) confirmé que
  el eje de horarios salta directamente de 12:00 a 13:20 sin generar una
  fila "fantasma" para el corte, y que el lunes completo aparece libre en
  todas sus franjas.

También volví a probar las 5 pantallas de reserva por HTTP real para
confirmar que no se rompió nada más.
