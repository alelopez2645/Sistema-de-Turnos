<div>
    @if ($mensaje)
        <div class="alert alert-info py-2 small">{{ $mensaje }}</div>
    @endif

    <div class="mb-3" style="max-width: 240px;">
        <label class="form-label small">Filtrar por estado</label>
        <select class="form-select form-select-sm" wire:model.live="filtroEstado">
            <option value="pendiente">Pendientes</option>
            <option value="aprobado">Aprobados</option>
            <option value="rechazado">Rechazados</option>
            <option value="cancelado">Cancelados</option>
            <option value="">Todos</option>
        </select>
    </div>

    @if ($turnos->isEmpty())
        <p class="text-muted">No hay turnos con ese estado.</p>
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
                            Docente: {{ $turno->docente->name }}
                            @if ($turno->carrera) · {{ $turno->carrera->nombre }} @endif
                            @if ($turno->curso) · Curso: {{ $turno->curso }} @endif
                            @if ($turno->cantidad_asistentes_aproximada) · ~{{ $turno->cantidad_asistentes_aproximada }} personas @endif
                        </div>
                        <div class="small mt-1">{{ $turno->motivo }}</div>

                        @if ($turno->nota_formal_path)
                            <div class="small mt-1">
                                <a href="{{ $turno->notaFormalUrl() }}" target="_blank">Ver nota formal adjunta</a>
                            </div>
                        @endif

                        @if ($turno->recepcionado_en || $turno->entregado_en)
                            <div class="small text-muted mt-2">
                                @if ($turno->recepcionado_en)
                                    Recepcionado el {{ $turno->recepcionado_en->format('d/m/Y H:i') }}
                                    @if ($turno->observaciones_recepcion) ("{{ $turno->observaciones_recepcion }}") @endif
                                @endif
                                @if ($turno->entregado_en)
                                    <br>Entregado el {{ $turno->entregado_en->format('d/m/Y H:i') }}
                                    @if ($turno->observaciones_entrega) ("{{ $turno->observaciones_entrega }}") @endif
                                @endif
                            </div>
                        @endif

                        @if ($turno->estado->value === 'pendiente')
                            <div class="mt-3">
                                <label class="form-label small">Observaciones (opcional)</label>
                                <input type="text" class="form-control form-control-sm mb-2" wire:model="observacionesPorTurno.{{ $turno->id }}" placeholder="Notas para el docente...">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-success" wire:click="aprobar({{ $turno->id }})">Aprobar</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="rechazar({{ $turno->id }})">Rechazar</button>
                                </div>
                            </div>
                        @elseif ($turno->observaciones)
                            <div class="small text-muted mt-2"><em>Obs.: {{ $turno->observaciones }}</em></div>
                        @endif

                        @if (in_array($turno->estado->value, ['pendiente', 'aprobado']))
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-secondary" wire:click="cancelar({{ $turno->id }})" wire:confirm="¿Cancelar este turno?">
                                    Cancelar turno
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">{{ $turnos->links() }}</div>
    @endif
</div>
