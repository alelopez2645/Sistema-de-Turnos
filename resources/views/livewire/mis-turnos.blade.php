<div>
    @if ($mensaje)
        <div class="alert alert-info py-2 small">{{ $mensaje }}</div>
    @endif

    @if ($turnos->isEmpty())
        <p class="text-muted">Todavía no solicitaste ningún turno.</p>
    @else
        <div class="d-flex flex-column gap-2">
            @foreach ($turnos as $turno)
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $turno->espacio->nombre }}</strong>
                                <span class="text-muted small ms-2">{{ $turno->fecha->format('d/m/Y') }} · {{ substr($turno->hora_inicio, 0, 5) }}–{{ substr($turno->hora_fin, 0, 5) }}</span>
                            </div>
                            @php
                                $colores = [
                                    'pendiente' => 'warning',
                                    'aprobado' => 'success',
                                    'rechazado' => 'danger',
                                    'cancelado' => 'secondary',
                                ];
                            @endphp
                            <span class="badge text-bg-{{ $colores[$turno->estado->value] ?? 'secondary' }}">{{ $turno->estado->value }}</span>
                        </div>

                        <div class="small text-muted mt-1">
                            @if ($turno->carrera) {{ $turno->carrera->nombre }} · @endif
                            @if ($turno->curso) Curso: {{ $turno->curso }} · @endif
                            @if ($turno->cantidad_asistentes_aproximada) ~{{ $turno->cantidad_asistentes_aproximada }} personas @endif
                        </div>
                        <div class="small mt-1">{{ $turno->motivo }}</div>

                        @if ($turno->estado->value === 'rechazado' && $turno->observaciones)
                            <div class="small text-danger mt-2"><em>Motivo del rechazo: {{ $turno->observaciones }}</em></div>
                        @elseif ($turno->observaciones)
                            <div class="small text-muted mt-2"><em>Obs. de administración: {{ $turno->observaciones }}</em></div>
                        @endif

                        @if ($turno->nota_formal_path)
                            <div class="small mt-2">
                                <a href="{{ $turno->notaFormalUrl() }}" target="_blank">Ver nota formal adjunta</a>
                            </div>
                        @endif

                        <div class="d-flex gap-2 mt-3">
                            @if ($turno->puedeModificarse())
                                <a href="{{ route('turnos.editar', $turno) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            @endif

                            @if (in_array($turno->estado->value, ['pendiente', 'aprobado']))
                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    wire:click="cancelar({{ $turno->id }})"
                                    wire:confirm="¿Seguro que querés cancelar este turno?"
                                >
                                    Cancelar
                                </button>
                            @endif

                            @if ($turno->puedeRecepcionarse())
                                <button class="btn btn-sm btn-outline-success" wire:click="abrirRecepcion({{ $turno->id }})">
                                    Registrar recepción
                                </button>
                            @endif

                            @if ($turno->puedeEntregarse())
                                <button class="btn btn-sm btn-outline-success" wire:click="abrirEntrega({{ $turno->id }})">
                                    Registrar entrega
                                </button>
                            @endif
                        </div>

                        @if ($turno->recepcionado_en)
                            <div class="small text-muted mt-2">
                                Recepcionado el {{ $turno->recepcionado_en->format('d/m/Y H:i') }}
                                @if ($turno->observaciones_recepcion) — "{{ $turno->observaciones_recepcion }}" @endif
                                @if (!empty($turno->imagenes_recepcion))
                                    <div class="d-flex gap-2 mt-1 flex-wrap">
                                        @foreach ($turno->imagenesRecepcionUrls() as $url)
                                            <a href="{{ $url }}" target="_blank">
                                                <img src="{{ $url }}" alt="Imagen de recepción" style="width:48px;height:48px;object-fit:cover;border-radius:.4rem;border:1px solid var(--ies-border);">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($turno->entregado_en)
                            <div class="small text-muted mt-2">
                                Entregado el {{ $turno->entregado_en->format('d/m/Y H:i') }}
                                @if ($turno->observaciones_entrega) — "{{ $turno->observaciones_entrega }}" @endif
                                @if (!empty($turno->imagenes_entrega))
                                    <div class="d-flex gap-2 mt-1 flex-wrap">
                                        @foreach ($turno->imagenesEntregaUrls() as $url)
                                            <a href="{{ $url }}" target="_blank">
                                                <img src="{{ $url }}" alt="Imagen de entrega" style="width:48px;height:48px;object-fit:cover;border-radius:.4rem;border:1px solid var(--ies-border);">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">{{ $turnos->links() }}</div>
    @endif

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
