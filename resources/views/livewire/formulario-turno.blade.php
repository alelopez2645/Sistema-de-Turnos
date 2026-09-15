<div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row text-muted small">
                @if ($espacio->capacidad)
                    <div class="col-auto"><strong>Capacidad:</strong> {{ $espacio->capacidad }}</div>
                @endif
                @if (in_array($tipo, [\App\Enums\TipoEspacio::TV_SMART, \App\Enums\TipoEspacio::PROYECTOR]))
                    <div class="col-auto"><strong>Unidades disponibles:</strong> {{ $espacio->unidades_disponibles }}</div>
                @endif
                @if ($espacio->equipamiento)
                    <div class="col-auto"><strong>Equipamiento:</strong> {{ $espacio->equipamiento }}</div>
                @endif
            </div>
            <div class="small text-muted mt-2">
                @if ($tipo->horasAnticipacionMinima() > 0)
                    Las reservas deben solicitarse con al menos {{ $tipo->horasAnticipacionMinima() }} horas de anticipación.
                @else
                    Las reservas solo pueden solicitarse para una fecha y horario futuros.
                @endif
                No se admiten reservas para sábados ni domingos.
                Los horarios se arman en bloques de {{ $tipo->intervaloMinutos() }} minutos.
                Hacé clic y arrastrá sobre el calendario para elegir el día y el horario.
            </div>
        </div>
    </div>

    @if ($mensajeExito)
        <div class="alert alert-success">{{ $mensajeExito }}</div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="semanaAnterior" title="Semana anterior">&laquo;</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="irAHoy">Hoy</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="semanaSiguiente" title="Semana siguiente">&raquo;</button>
        </div>
        <div class="fw-semibold">{{ $rangoSemana }}</div>
    </div>

    <div class="calendario-semanal-wrap mb-3">
        <div class="calendario-semanal" x-data="calendarioDrag()" @mouseup.window="finalizar()" @touchend.window="finalizar()">
            <div class="fila-header">
                <div class="celda-esquina" style="grid-column: 1; grid-row: 1;"></div>
                @foreach ($dias as $diaIndex => $dia)
                    <div
                        class="celda-dia-header @if ($dia['esHoy']) es-hoy @endif @if ($dia['esFinDeSemana']) es-finde @endif"
                        style="grid-column: {{ $diaIndex + 2 }}; grid-row: 1;"
                    >
                        <span class="dia-nombre">{{ $dia['etiqueta'] }}</span>
                        <span class="dia-fecha">{{ $dia['diaMes'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Todas las celdas (horario, y cada día) se ubican con
                 grid-column/grid-row EXPLÍCITOS a propósito: si se dejan en
                 modo automático, los bloques de reserva de más abajo (que sí
                 tienen posición explícita porque ocupan varias filas) hacen
                 que el navegador corra de lugar las celdas automáticas de esa
                 misma fila — eso es lo que causaba los horarios de la
                 izquierda apareciendo en columnas equivocadas y celdas
                 "no disponible" en lugares sueltos que en realidad estaban
                 libres. Con posición explícita en todo, cada celda queda
                 siempre en su día/horario real. --}}
            @foreach ($filas as $index => $fila)
                @php $esHoraEnPunto = str_ends_with($fila['inicio'], ':00'); @endphp
                <div class="fila-horario">
                    <div
                        class="celda-hora @if ($esHoraEnPunto) hora-en-punto @endif"
                        style="grid-column: 1; grid-row: {{ $index + 2 }};"
                    >
                        {{ $fila['inicio'] }}
                    </div>

                    @foreach ($dias as $diaIndex => $dia)
                        @php $b = $bloques[$dia['fecha']][$fila['inicio']]; @endphp
                        <div
                            class="celda-turno
                                @if (!$b['disponibleEnAgenda']) celda-fuera @endif
                                @if ($b['pasado']) celda-pasado @endif
                                @if (!$b['seleccionable']) celda-bloqueada @endif"
                            style="grid-column: {{ $diaIndex + 2 }}; grid-row: {{ $index + 2 }};"
                            data-fecha="{{ $dia['fecha'] }}"
                            data-inicio="{{ $fila['inicio'] }}"
                            data-fin="{{ $fila['fin'] }}"
                            data-index="{{ $index }}"
                            @if ($b['seleccionable'])
                                @mousedown.prevent="iniciar($event)"
                                @mouseenter="continuar($event)"
                                @touchstart.prevent="iniciar($event)"
                                @touchmove.prevent="continuarTouch($event)"
                            @endif
                        ></div>
                    @endforeach
                </div>
            @endforeach

            {{-- Bloques de reserva: uno por cada turno, ocupando todas las filas de
                 su horario (grid-row: span) con su contenido adentro, en vez de
                 repetirse en cada franja. Van "encima" de la grilla de celdas de
                 arriba (misma fila/columna), sin bloquear el arrastre porque no
                 reciben eventos de mouse (pointer-events: none). --}}
            @foreach ($dias as $diaIndex => $dia)
                @foreach ($eventosPorDia[$dia['fecha']] as $evento)
                    @php
                        $anchoCarril = 100 / $evento['lanes'];
                    @endphp
                    <div
                        class="evento-turno evento-{{ $evento['estado'] }} @if ($evento['esPropio']) evento-propio @endif"
                        style="
                            grid-column: {{ $diaIndex + 2 }};
                            grid-row: {{ $evento['filaInicio'] + 2 }} / span {{ $evento['span'] }};
                            width: calc({{ $anchoCarril }}% - 2px);
                            margin-left: calc({{ $anchoCarril }}% * {{ $evento['lane'] }});
                        "
                    >
                        <span class="evento-horario">{{ $evento['horario'] }}</span>
                        <span class="evento-titulo">{{ $evento['motivo'] }}</span>
                        <span class="evento-meta">{{ $evento['docente'] }}@if ($evento['carrera'] !== 'Sin carrera') · {{ $evento['carrera'] }} @endif</span>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    <div class="calendario-leyenda small text-muted mb-3 d-flex flex-wrap gap-3">
        <span><i class="leyenda-dot leyenda-libre"></i> Libre</span>
        <span><i class="leyenda-dot leyenda-pendiente"></i> Pendiente</span>
        <span><i class="leyenda-dot leyenda-aprobado"></i> Aprobado</span>
        <span><i class="leyenda-dot leyenda-propia"></i> Tu reserva</span>
        <span><i class="leyenda-dot leyenda-bloqueada"></i> No disponible</span>
    </div>


    <div class="card mb-3">
        <div class="card-header py-2">
            <span class="fw-semibold small">Reservas de la semana ({{ $rangoSemana }})</span>
            <span class="text-muted small">— de todos los docentes</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr class="small text-muted">
                        <th>Día</th>
                        <th>Horario</th>
                        <th>Docente</th>
                        <th>Carrera</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservasSemana as $reserva)
                        <tr class="small @if ($reserva->docente_id === auth()->id()) table-light @endif">
                            <td>{{ $reserva->fecha->translatedFormat('D d/m') }}</td>
                            <td>{{ substr($reserva->hora_inicio, 0, 5) }} – {{ substr($reserva->hora_fin, 0, 5) }}</td>
                            <td>{{ $reserva->docente?->name ?? 'Docente' }}</td>
                            <td>{{ $reserva->carrera?->nombre ?? '—' }}</td>
                            <td class="text-truncate" style="max-width: 240px;" title="{{ $reserva->motivo }}">{{ $reserva->motivo }}</td>
                            <td>
                                <span class="badge {{ $reserva->estado->value === 'aprobado' ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ ucfirst($reserva->estado->value) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted small text-center py-3">No hay reservas cargadas para esta semana.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Popup flotante: Nueva Reserva (se abre al soltar el arrastre en el calendario) --}}
    @if ($mostrarModalReserva)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva Reserva</h5>
                        <button type="button" class="btn-close" wire:click="cerrarModalReserva" aria-label="Cerrar"></button>
                    </div>
                    <form wire:submit="intentarGuardar">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Fecha</label>
                                    <input type="date" class="form-control" value="{{ $fecha }}" disabled>
                                    @error('fecha') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Hora de entrada</label>
                                    <input type="text" class="form-control" value="{{ $hora_inicio ? $this->formatoAmPm($hora_inicio) : '' }}" disabled>
                                    @error('hora_inicio') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Hora de salida</label>
                                    <input type="text" class="form-control" value="{{ $hora_fin ? $this->formatoAmPm($hora_fin) : '' }}" disabled>
                                    @error('hora_fin') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Carrera</label>
                                    <select class="form-select @error('carrera_id') is-invalid @enderror" wire:model="carrera_id">
                                        <option value="">Seleccioná una carrera</option>
                                        @foreach ($carreras as $carrera)
                                            <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('carrera_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Curso</label>
                                    <select class="form-select @error('curso') is-invalid @enderror" wire:model="curso">
                                        <option value="">Seleccioná el curso</option>
                                        @foreach (\App\Models\Turno::CURSOS as $c)
                                            <option value="{{ $c }}">{{ $c }}</option>
                                        @endforeach
                                    </select>
                                    @error('curso') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                @if ($tipo->requiereCantidadAsistentes())
                                    <div class="col-md-6">
                                        <label class="form-label small">Cantidad aproximada de asistentes</label>
                                        <input type="number" min="1" @if ($espacio->capacidad) max="{{ $espacio->capacidad }}" @endif class="form-control @error('cantidad_asistentes_aproximada') is-invalid @enderror" wire:model="cantidad_asistentes_aproximada">
                                        @error('cantidad_asistentes_aproximada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                @endif

                                <div class="col-12">
                                    <label class="form-label small">Motivo de la reserva</label>
                                    <textarea rows="2" class="form-control @error('motivo') is-invalid @enderror" wire:model="motivo" placeholder="Ej. Reunión de equipo, Capacitación, Presentación..."></textarea>
                                    @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                @if ($tipo->requiereNotaFormal())
                                    <div class="col-12">
                                        <label class="form-label small">Nota formal del trámite administrativo (imagen)</label>
                                        <input type="file" accept="image/*" class="form-control @error('notaFormal') is-invalid @enderror" wire:model="notaFormal">
                                        @error('notaFormal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div wire:loading wire:target="notaFormal" class="small text-muted mt-1">Subiendo imagen...</div>
                                        @if ($notaFormal)
                                            <div class="small text-success mt-1">Archivo listo: {{ $notaFormal->getClientOriginalName() }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cerrarModalReserva">Cancelar</button>
                            <button type="submit" class="btn btn-ies-accent btn-sm" wire:loading.attr="disabled" wire:target="intentarGuardar,notaFormal">
                                <span wire:loading.remove wire:target="intentarGuardar">Crear Reserva</span>
                                <span wire:loading wire:target="intentarGuardar">Validando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Popup de condiciones de uso: aparece después de "Crear Reserva", antes de guardar --}}
    @if ($mostrarModalTerminos)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Condiciones de uso</h5>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">
                            Para el uso responsable de <strong>{{ $espacio->nombre }}</strong> les solicitamos tener en cuenta las siguientes consideraciones:
                        </p>
                        <ul class="small">
                            @foreach ($reglasUso as $regla)
                                <li>{{ $regla }}</li>
                            @endforeach
                        </ul>

                        <div class="form-check mt-3">
                            <input class="form-check-input @error('terminosAceptados') is-invalid @enderror" type="checkbox" wire:model="terminosAceptados" id="terminosAceptados">
                            <label class="form-check-label small" for="terminosAceptados">
                                Leí y acepto las condiciones de uso del espacio.
                            </label>
                            @error('terminosAceptados') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cerrarModalTerminos">Volver</button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="confirmarReserva" wire:loading.attr="disabled" wire:target="confirmarReserva">
                            <span wire:loading.remove wire:target="confirmarReserva">Confirmar reserva</span>
                            <span wire:loading wire:target="confirmarReserva">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        Alpine.data('calendarioDrag', () => ({
            arrastrando: false,
            dia: null,
            inicioIdx: null,
            finIdx: null,

            iniciar(e) {
                const el = e.currentTarget;
                this.arrastrando = true;
                this.dia = el.dataset.fecha;
                this.inicioIdx = parseInt(el.dataset.index, 10);
                this.finIdx = this.inicioIdx;
                this.pintar();
            },

            continuar(e) {
                if (!this.arrastrando) return;

                const el = e.currentTarget;
                if (!el.dataset.fecha || el.dataset.fecha !== this.dia) return;

                const hoverIdx = parseInt(el.dataset.index, 10);
                const direccion = hoverIdx >= this.inicioIdx ? 1 : -1;
                let limite = this.inicioIdx;

                // Recorre celda por celda desde el inicio del arrastre hasta la
                // celda sobre la que está el mouse ahora, y se detiene apenas
                // encuentra una celda no disponible (para no poder arrastrar
                // "por encima" de un horario ya reservado).
                for (let i = this.inicioIdx; direccion > 0 ? i <= hoverIdx : i >= hoverIdx; i += direccion) {
                    const celda = this.$root.querySelector('.celda-turno[data-fecha="' + this.dia + '"][data-index="' + i + '"]');
                    if (!celda || celda.classList.contains('celda-bloqueada')) break;
                    limite = i;
                }

                this.finIdx = limite;
                this.pintar();
            },

            continuarTouch(e) {
                const touch = e.touches[0];
                if (!touch) return;
                const el = document.elementFromPoint(touch.clientX, touch.clientY);
                const celda = el ? el.closest('.celda-turno') : null;
                if (celda) this.continuar({ currentTarget: celda });
            },

            pintar() {
                this.$root.querySelectorAll('.celda-turno.celda-en-arrastre').forEach((c) => c.classList.remove('celda-en-arrastre'));
                if (!this.arrastrando) return;

                const min = Math.min(this.inicioIdx, this.finIdx);
                const max = Math.max(this.inicioIdx, this.finIdx);

                this.$root.querySelectorAll('.celda-turno[data-fecha="' + this.dia + '"]').forEach((c) => {
                    const idx = parseInt(c.dataset.index, 10);
                    if (idx >= min && idx <= max) c.classList.add('celda-en-arrastre');
                });
            },

            finalizar() {
                if (!this.arrastrando) return;
                this.arrastrando = false;

                const min = Math.min(this.inicioIdx, this.finIdx);
                const max = Math.max(this.inicioIdx, this.finIdx);
                const dia = this.dia;

                const celdas = Array.from(this.$root.querySelectorAll('.celda-turno[data-fecha="' + dia + '"]'))
                    .filter((c) => {
                        const idx = parseInt(c.dataset.index, 10);
                        return idx >= min && idx <= max;
                    })
                    .sort((a, b) => parseInt(a.dataset.index, 10) - parseInt(b.dataset.index, 10));

                this.$root.querySelectorAll('.celda-turno.celda-en-arrastre').forEach((c) => c.classList.remove('celda-en-arrastre'));

                if (!celdas.length) return;

                const horaInicio = celdas[0].dataset.inicio;
                const horaFin = celdas[celdas.length - 1].dataset.fin;

                $wire.abrirNuevaReserva(dia, horaInicio, horaFin);
            },
        }));
    </script>
    @endscript
</div>
