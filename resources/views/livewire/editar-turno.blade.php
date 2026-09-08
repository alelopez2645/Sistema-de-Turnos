<div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row text-muted small">
                <div class="col-auto"><strong>Espacio:</strong> {{ $turno->espacio->nombre }}</div>
                @if ($turno->espacio->capacidad)
                    <div class="col-auto"><strong>Capacidad:</strong> {{ $turno->espacio->capacidad }}</div>
                @endif
            </div>
            <div class="small text-muted mt-2">
                Podés modificar este turno hasta {{ \App\Models\Turno::VENTANA_MODIFICACION_HORAS }} horas antes del horario reservado.
                Si el turno ya estaba aprobado, al modificarlo vuelve a quedar pendiente de revisión por administración.
                No se admiten reservas para sábados ni domingos.
                @if ($turno->espacio->tipo->usaPaquetesHorarios())
                    Los horarios se arman en paquetes de {{ $turno->espacio->tipo->intervaloMinutos() }} minutos.
                @endif
            </div>
        </div>
    </div>

    @if ($mensajeExito)
        <div class="alert alert-success">{{ $mensajeExito }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form wire:submit="actualizar">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">Fecha</label>
                        <input type="date" class="form-control @error('fecha') is-invalid @enderror" wire:model.live="fecha" min="{{ now()->toDateString() }}">
                        @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

                    @if ($turno->espacio->tipo->usaPaquetesHorarios())
                        <div class="col-md-6">
                            <label class="form-label small">Hora de inicio</label>
                            <select class="form-select @error('hora_inicio') is-invalid @enderror" wire:model.live="hora_inicio" @disabled(empty($horasInicioDisponibles))>
                                <option value="">Seleccioná un horario</option>
                                @foreach ($horasInicioDisponibles as $h)
                                    <option value="{{ $h['valor'] }}" @disabled(!$h['disponible'])>{{ $h['valor'] }}@if (!$h['disponible']) (reservado) @endif</option>
                                @endforeach
                            </select>
                            @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if ($fecha && empty($horasInicioDisponibles))
                                <div class="form-text text-danger">No hay horarios habilitados por administración para esa fecha.</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Hora de fin</label>
                            <select class="form-select @error('hora_fin') is-invalid @enderror" wire:model="hora_fin" @disabled(empty($horasFinDisponibles))>
                                <option value="">Seleccioná un horario</option>
                                @foreach ($horasFinDisponibles as $h)
                                    <option value="{{ $h['valor'] }}" @disabled(!$h['disponible'])>{{ $h['valor'] }}@if (!$h['disponible']) (reservado) @endif</option>
                                @endforeach
                            </select>
                            @error('hora_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <div class="col-md-6">
                            <label class="form-label small">Hora de inicio</label>
                            <input type="time" class="form-control @error('hora_inicio') is-invalid @enderror" wire:model="hora_inicio">
                            @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Hora de fin</label>
                            <input type="time" class="form-control @error('hora_fin') is-invalid @enderror" wire:model="hora_fin">
                            @error('hora_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-12">
                        <label class="form-label small">Curso</label>
                        <select class="form-select @error('curso') is-invalid @enderror" wire:model="curso">
                            <option value="">Seleccioná el curso</option>
                            @foreach (\App\Models\Turno::CURSOS as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('curso') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small">Motivo</label>
                        <input type="text" class="form-control @error('motivo') is-invalid @enderror" wire:model="motivo">
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if ($turno->espacio->tipo->requiereCantidadAsistentes())
                        <div class="col-md-6">
                            <label class="form-label small">Cantidad aproximada de asistentes</label>
                            <input type="number" min="1" @if ($turno->espacio->capacidad) max="{{ $turno->espacio->capacidad }}" @endif class="form-control @error('cantidad_asistentes_aproximada') is-invalid @enderror" wire:model="cantidad_asistentes_aproximada">
                            @error('cantidad_asistentes_aproximada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    @if ($turno->espacio->tipo->requiereNotaFormal())
                        <div class="col-12">
                            <label class="form-label small">Reemplazar nota formal (opcional)</label>
                            @if ($turno->nota_formal_path)
                                <div class="small mb-1">
                                    Nota actual: <a href="{{ $turno->notaFormalUrl() }}" target="_blank">ver imagen</a>
                                </div>
                            @endif
                            <input type="file" accept="image/*" class="form-control @error('notaFormal') is-invalid @enderror" wire:model="notaFormal">
                            @error('notaFormal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div wire:loading wire:target="notaFormal" class="small text-muted mt-1">Subiendo imagen...</div>
                            @if ($notaFormal)
                                <div class="small text-success mt-1">Nuevo archivo listo: {{ $notaFormal->getClientOriginalName() }}</div>
                            @endif
                        </div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary mt-4" wire:loading.attr="disabled" wire:target="actualizar,notaFormal">
                    <span wire:loading.remove wire:target="actualizar">Guardar cambios</span>
                    <span wire:loading wire:target="actualizar">Guardando...</span>
                </button>
            </form>
        </div>
    </div>

    @if (!empty($horariosOcupados))
        <div class="card mt-3">
            <div class="card-body">
                <h2 class="h6">Horarios ya solicitados/confirmados para esa fecha</h2>
                <ul class="mb-0 small text-muted">
                    @foreach ($horariosOcupados as $h)
                        <li>{{ substr($h['hora_inicio'], 0, 5) }} – {{ substr($h['hora_fin'], 0, 5) }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
