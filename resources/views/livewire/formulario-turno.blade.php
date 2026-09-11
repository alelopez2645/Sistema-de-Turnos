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
                @if ($tipo->usaPaquetesHorarios())
                    Los horarios se arman en paquetes de {{ $tipo->intervaloMinutos() }} minutos.
                @endif
            </div>
        </div>
    </div>

    @if ($mensajeExito)
        <div class="alert alert-success">{{ $mensajeExito }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form wire:submit="intentarGuardar">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small d-block">Fecha</label>
                        @include('partials.calendario-fecha')
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

                    @if ($tipo->usaPaquetesHorarios())
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
                        <input type="text" class="form-control @error('motivo') is-invalid @enderror" wire:model="motivo" placeholder="Describí brevemente el motivo del turno">
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if ($tipo->requiereCantidadAsistentes())
                        <div class="col-md-6">
                            <label class="form-label small">Cantidad aproximada de asistentes</label>
                            <input type="number" min="1" @if ($espacio->capacidad) max="{{ $espacio->capacidad }}" @endif class="form-control @error('cantidad_asistentes_aproximada') is-invalid @enderror" wire:model="cantidad_asistentes_aproximada">
                            @error('cantidad_asistentes_aproximada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

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

                <button type="submit" class="btn btn-primary mt-4" wire:loading.attr="disabled" wire:target="intentarGuardar,notaFormal">
                    <span wire:loading.remove wire:target="intentarGuardar">Solicitar turno</span>
                    <span wire:loading wire:target="intentarGuardar">Enviando...</span>
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
                @if (in_array($tipo, [\App\Enums\TipoEspacio::TV_SMART, \App\Enums\TipoEspacio::PROYECTOR]))
                    <div class="small text-muted mt-2">
                        Hay {{ $espacio->unidades_disponibles }} unidades en total: si en un horario ya figuran solicitudes pero no llegan a esa cantidad, todavía podés reservar.
                    </div>
                @endif
            </div>
        </div>
    @endif

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
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cerrarModalTerminos">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="confirmarReserva" wire:loading.attr="disabled" wire:target="confirmarReserva">
                            <span wire:loading.remove wire:target="confirmarReserva">Confirmar reserva</span>
                            <span wire:loading wire:target="confirmarReserva">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
