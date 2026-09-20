<div>
    @if ($mensaje)
        <div class="alert alert-info py-2 small">{{ $mensaje }}</div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="semanaAnterior" title="Semana anterior">&laquo;</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="irAHoy">Hoy</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="semanaSiguiente" title="Semana siguiente">&raquo;</button>
        </div>
        <div class="fw-semibold">{{ $rangoSemana }}</div>
    </div>

    <div class="calendario-leyenda small text-muted mb-2 d-flex flex-wrap gap-3">
        <span><i class="leyenda-dot leyenda-pendiente"></i> Pendiente</span>
        <span><i class="leyenda-dot leyenda-aprobado"></i> Aprobado</span>
        <span><i class="leyenda-dot leyenda-rechazado"></i> Rechazado</span>
        <span><i class="leyenda-dot leyenda-cancelado"></i> Cancelado</span>
    </div>

    <div class="mis-turnos-cal-wrap mb-3">
        <div class="mis-turnos-cal">
            <div class="mtc-header-row">
                <div class="mtc-esquina"></div>
                @foreach ($dias as $dia)
                    <div class="mtc-dia-header @if ($dia['esHoy']) es-hoy @endif">
                        <span class="dia-nombre">{{ $dia['etiqueta'] }}</span>
                        <span class="dia-fecha">{{ $dia['diaMes'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mtc-body-row">
                <div class="mtc-horas" style="height: {{ $altoTotal }}px;">
                    @foreach ($horas as $h)
                        <div class="mtc-hora-label" style="height: 48px;">{{ sprintf('%02d:00', $h) }}</div>
                    @endforeach
                </div>

                @foreach ($dias as $dia)
                    <div class="mtc-dia-col" style="height: {{ $altoTotal }}px;">
                        @foreach ($horas as $h)
                            <div class="mtc-linea-hora" style="top: {{ ($h - 8) * 48 }}px;"></div>
                        @endforeach

                        @foreach ($eventosPorDia[$dia['fecha']] as $ev)
                            @php $ancho = 100 / $ev['lanes']; @endphp
                            <div
                                class="mtc-evento mtc-evento-{{ $ev['estado'] }}"
                                style="
                                    top: {{ $ev['top'] }}px;
                                    height: {{ $ev['alto'] }}px;
                                    width: calc({{ $ancho }}% - 2px);
                                    left: calc({{ $ancho }}% * {{ $ev['lane'] }});
                                "
                                wire:click="abrirDetalle({{ $ev['turno_id'] }})"
                                title="{{ $ev['espacio'] }} — {{ $ev['horario'] }} ({{ $ev['estado'] }})"
                            >
                                <span class="mtc-evento-espacio">{{ $ev['espacio'] }}</span>
                                <span class="mtc-evento-horario">{{ $ev['horario'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Popup de detalle: se abre al hacer clic en un turno del calendario --}}
    @if ($turnoEnDetalle)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $turnoEnDetalle->espacio->nombre }}</h5>
                        <button type="button" class="btn-close" wire:click="cerrarDetalle" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-muted small">
                                {{ $turnoEnDetalle->fecha->format('d/m/Y') }} ·
                                {{ substr($turnoEnDetalle->hora_inicio, 0, 5) }}–{{ substr($turnoEnDetalle->hora_fin, 0, 5) }}
                            </span>
                            @php
                                $colores = [
                                    'pendiente' => 'warning',
                                    'aprobado' => 'success',
                                    'rechazado' => 'danger',
                                    'cancelado' => 'secondary',
                                ];
                            @endphp
                            <span class="badge text-bg-{{ $colores[$turnoEnDetalle->estado->value] ?? 'secondary' }}">{{ $turnoEnDetalle->estado->value }}</span>
                        </div>

                        <div class="small text-muted mb-1">
                            @if ($turnoEnDetalle->carrera) {{ $turnoEnDetalle->carrera->nombre }} · @endif
                            @if ($turnoEnDetalle->curso) Curso: {{ $turnoEnDetalle->curso }} · @endif
                            @if ($turnoEnDetalle->cantidad_asistentes_aproximada) ~{{ $turnoEnDetalle->cantidad_asistentes_aproximada }} personas @endif
                        </div>
                        <div class="small mb-2">{{ $turnoEnDetalle->motivo }}</div>

                        @if ($turnoEnDetalle->estado->value === 'rechazado' && $turnoEnDetalle->observaciones)
                            <div class="small text-danger mb-2"><em>Motivo del rechazo: {{ $turnoEnDetalle->observaciones }}</em></div>
                        @elseif ($turnoEnDetalle->observaciones)
                            <div class="small text-muted mb-2"><em>Obs. de administración: {{ $turnoEnDetalle->observaciones }}</em></div>
                        @endif

                        @if ($turnoEnDetalle->nota_formal_path)
                            <div class="small mb-2">
                                <a href="{{ $turnoEnDetalle->notaFormalUrl() }}" target="_blank">Ver nota formal adjunta</a>
                            </div>
                        @endif

                        @if ($turnoEnDetalle->recepcionado_en)
                            <div class="small text-muted border-top pt-2 mt-2">
                                Recepcionado el {{ $turnoEnDetalle->recepcionado_en->format('d/m/Y H:i') }}
                                @if ($turnoEnDetalle->observaciones_recepcion) — "{{ $turnoEnDetalle->observaciones_recepcion }}" @endif
                                @if (!empty($turnoEnDetalle->imagenes_recepcion))
                                    <div class="d-flex gap-2 mt-1 flex-wrap">
                                        @foreach ($turnoEnDetalle->imagenesRecepcionUrls() as $url)
                                            <a href="{{ $url }}" target="_blank">
                                                <img src="{{ $url }}" alt="Imagen de recepción" style="width:48px;height:48px;object-fit:cover;border-radius:.4rem;border:1px solid var(--ies-border);">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($turnoEnDetalle->entregado_en)
                            <div class="small text-muted border-top pt-2 mt-2">
                                Entregado el {{ $turnoEnDetalle->entregado_en->format('d/m/Y H:i') }}
                                @if ($turnoEnDetalle->observaciones_entrega) — "{{ $turnoEnDetalle->observaciones_entrega }}" @endif
                                @if (!empty($turnoEnDetalle->imagenes_entrega))
                                    <div class="d-flex gap-2 mt-1 flex-wrap">
                                        @foreach ($turnoEnDetalle->imagenesEntregaUrls() as $url)
                                            <a href="{{ $url }}" target="_blank">
                                                <img src="{{ $url }}" alt="Imagen de entrega" style="width:48px;height:48px;object-fit:cover;border-radius:.4rem;border:1px solid var(--ies-border);">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer flex-wrap gap-2">
                        @if ($turnoEnDetalle->puedeModificarse())
                            <a href="{{ route('turnos.editar', $turnoEnDetalle) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        @endif

                        @if (in_array($turnoEnDetalle->estado->value, ['pendiente', 'aprobado']))
                            <button
                                class="btn btn-sm btn-outline-danger"
                                wire:click="cancelar({{ $turnoEnDetalle->id }})"
                                wire:confirm="¿Seguro que querés cancelar este turno?"
                            >
                                Cancelar
                            </button>
                        @endif

                        @if ($turnoEnDetalle->puedeRecepcionarse())
                            <button class="btn btn-sm btn-outline-success" wire:click="abrirRecepcion({{ $turnoEnDetalle->id }})">
                                Registrar recepción
                            </button>
                        @endif

                        @if ($turnoEnDetalle->puedeEntregarse())
                            <button class="btn btn-sm btn-outline-success" wire:click="abrirEntrega({{ $turnoEnDetalle->id }})">
                                Registrar entrega
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de recepción / entrega: se conserva exactamente igual que antes --}}
    @if ($turnoModalRecepcion || $turnoModalEntrega)
        @php
            $esRecepcion = (bool) $turnoModalRecepcion;
        @endphp
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $esRecepcion ? 'Registrar recepción' : 'Registrar entrega' }}</h5>
                    </div>
                    <div class="modal-body">
                        @if ($turnoEnModal)
                            <p class="small text-muted mb-3">
                                {{ $turnoEnModal->espacio->nombre }} · {{ $turnoEnModal->fecha->format('d/m/Y') }} ·
                                {{ substr($turnoEnModal->hora_inicio, 0, 5) }}–{{ substr($turnoEnModal->hora_fin, 0, 5) }}
                            </p>
                        @endif

                        <label class="form-label small">
                            {{ $esRecepcion ? '¿En qué condiciones recibís el espacio o equipo?' : '¿En qué condiciones lo entregás?' }}
                        </label>
                        <textarea
                            class="form-control form-control-sm @error('observacionesModal') is-invalid @enderror"
                            rows="3"
                            wire:model="observacionesModal"
                            placeholder="Observaciones (opcional)"
                        ></textarea>
                        @error('observacionesModal') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <label class="form-label small mt-3">Imágenes (opcional, hasta 5)</label>
                        <input
                            type="file"
                            accept="image/*"
                            multiple
                            class="form-control form-control-sm @error('imagenesModal') is-invalid @enderror @error('imagenesModal.*') is-invalid @enderror"
                            wire:model="imagenesModal"
                        >
                        @error('imagenesModal') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('imagenesModal.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                        <div wire:loading wire:target="imagenesModal" class="small text-muted mt-1">Subiendo imágenes...</div>

                        @if (!empty($imagenesModal))
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                @foreach ($imagenesModal as $imagen)
                                    <span class="badge text-bg-light border small">{{ $imagen->getClientOriginalName() }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cerrarModal">Cancelar</button>
                        <button
                            type="button"
                            class="btn btn-success btn-sm"
                            wire:click="{{ $esRecepcion ? 'confirmarRecepcion' : 'confirmarEntrega' }}"
                            wire:loading.attr="disabled"
                            wire:target="confirmarRecepcion,confirmarEntrega,imagenesModal"
                        >
                            <span wire:loading.remove wire:target="confirmarRecepcion,confirmarEntrega">
                                Confirmar {{ $esRecepcion ? 'recepción' : 'entrega' }}
                            </span>
                            <span wire:loading wire:target="confirmarRecepcion,confirmarEntrega">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
