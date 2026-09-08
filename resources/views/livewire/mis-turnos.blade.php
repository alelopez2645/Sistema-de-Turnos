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
                        </div>

                        @if ($turno->puedeRecepcionarse())
                            <div class="border-top mt-3 pt-3">
                                <label class="form-label small">Registrar recepción del espacio: ¿en qué condiciones lo recibís?</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" wire:model="observacionesRecepcionPorTurno.{{ $turno->id }}" placeholder="Ej: todo en orden, proyector funcionando...">
                                    <button class="btn btn-outline-success" wire:click="recepcionar({{ $turno->id }})">Registrar recepción</button>
                                </div>
                            </div>
                        @elseif ($turno->recepcionado_en)
                            <div class="small text-muted mt-2">
                                Recepcionado el {{ $turno->recepcionado_en->format('d/m/Y H:i') }}
                                @if ($turno->observaciones_recepcion) — "{{ $turno->observaciones_recepcion }}" @endif
                            </div>
                        @endif

                        @if ($turno->puedeEntregarse())
                            <div class="border-top mt-3 pt-3">
                                <label class="form-label small">Registrar entrega del espacio: ¿en qué condiciones lo dejás?</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" wire:model="observacionesEntregaPorTurno.{{ $turno->id }}" placeholder="Ej: se devolvió limpio, sin novedades...">
                                    <button class="btn btn-outline-success" wire:click="entregar({{ $turno->id }})">Registrar entrega</button>
                                </div>
                            </div>
                        @elseif ($turno->entregado_en)
                            <div class="small text-muted mt-2">
                                Entregado el {{ $turno->entregado_en->format('d/m/Y H:i') }}
                                @if ($turno->observaciones_entrega) — "{{ $turno->observaciones_entrega }}" @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">{{ $turnos->links() }}</div>
    @endif
</div>
