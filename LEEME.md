# "Mis Turnos" como calendario (con popup de detalle y los 4 botones)

## Archivos modificados (completos, no parches)
- app/Livewire/MisTurnos.php
- resources/views/livewire/mis-turnos.blade.php
- resources/views/layouts/app.blade.php (se agregó el bloque de CSS del
  calendario nuevo; el resto del archivo queda igual)

## Cómo aplicar
Reemplazá los 3 archivos por estos. No hace falta migración ni tocar la
base de datos.

## Qué cambia
"Mis Turnos" ahora se ve como un calendario semanal (igual estilo que la
pantalla de reservar), con las mismas franjas horarias, en vez de la
lista de tarjetas de antes. Como acá conviven turnos de distintos
espacios (Auditorio, Informática, TV Smart, etc., cada uno con su propia
duración de módulo), los bloques no están atados a una grilla fija de
franjas: cada turno se dibuja directamente en su horario real, con su
espacio y horario adentro. Si dos turnos tuyos se superponen en el
tiempo (por ejemplo reservaste el Auditorio y un TV Smart a la misma
hora), se acomodan lado a lado en vez de taparse.

- Navegación por semana (`«` `Hoy` `»`), igual que en Solicitar Turnos.
- Colores por estado: naranja = pendiente, azul institucional = aprobado,
  rojo = rechazado, gris tachado = cancelado.
- **Clic en cualquier turno** abre un popup con toda la información
  (espacio, fecha, horario, estado, carrera, curso, cantidad de
  asistentes, motivo, observaciones de administración o motivo de
  rechazo, nota formal adjunta si tiene) y, abajo, los botones que
  correspondan:
  - **Editar** — solo si el turno todavía se puede modificar (pendiente
    o aprobado, con más de 24hs de anticipación). Es un link directo a
    la pantalla de edición de siempre, no la toqué.
  - **Cancelar** — solo si está pendiente o aprobado.
  - **Registrar recepción** — solo si está aprobado y todavía no se
    registró la recepción.
  - **Registrar entrega** — solo si ya se registró la recepción y
    todavía no la entrega. Este botón ya existía en el código (por eso
    no te aparecía en la lista vieja: ninguno de esos 3 turnos tenía la
    recepción hecha todavía), simplemente ahora vive en el popup en vez
    de en la tarjeta.

**Conservé el formulario de recepción/entrega tal cual estaba** (el
textarea de observaciones + hasta 5 imágenes): al tocar "Registrar
recepción" o "Registrar entrega" desde el popup de detalle, se cierra
ese popup y se abre exactamente el mismo formulario de siempre, sin
ningún cambio en su lógica.

## Cómo lo probé
Probé el algoritmo que reparte los turnos superpuestos en carriles de
forma aislada (con y sin solapamiento). Después armé un escenario
completo: un turno de Auditorio aprobado que se solapa a propósito con
uno de TV Smart pendiente (para ver el reparto en carriles), y uno de
Proyector rechazado en otro día. Confirmé que el calendario los muestra
a los tres con su espacio y color de estado correctos, que al hacer clic
en el de Auditorio aparecen exactamente los botones que corresponden
(Editar, Cancelar, Registrar recepción — pero NO "Registrar entrega"
todavía), que registrar la recepción desde ahí guarda bien los datos y
cierra el popup correcto, que una vez recepcionado SÍ aparece "Registrar
entrega" y ya no "Registrar recepción", y que cancelar un turno desde el
detalle cambia el estado y cierra el popup. También probé la navegación
entre semanas y las rutas por HTTP real para docente y administrador.
