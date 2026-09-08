<div>
    @if ($mensaje)
        <div class="alert alert-success py-2 small">{{ $mensaje }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">Agregar franja horaria</h2>
            <form wire:submit="agregar">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Espacio</label>
                        <select class="form-select form-select-sm @error('espacio_id') is-invalid @enderror" wire:model="espacio_id">
                            <option value="">Seleccioná un espacio</option>
                            @foreach ($espacios as $espacio)
                                <option value="{{ $espacio->id }}">{{ $espacio->nombre }}</option>
                            @endforeach
                        </select>
                        @error('espacio_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Día</label>
                        <select class="form-select form-select-sm" wire:model="dia_semana">
                            <option value="1">Lunes</option>
                            <option value="2">Martes</option>
                            <option value="3">Miércoles</option>
                            <option value="4">Jueves</option>
                            <option value="5">Viernes</option>
                            <option value="6">Sábado</option>
                            <option value="0">Domingo</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small">Desde</label>
                        <input type="time" class="form-control form-control-sm @error('hora_inicio') is-invalid @enderror" wire:model="hora_inicio">
                        @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small">Hasta</label>
                        <input type="time" class="form-control form-control-sm @error('hora_fin') is-invalid @enderror" wire:model="hora_fin">
                        @error('hora_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-primary w-100">+</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php
        $dias = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
    @endphp

    <div class="table-responsive">
        <table class="table table-sm bg-white align-middle">
            <thead>
                <tr class="small text-muted">
                    <th>Espacio</th>
                    <th>Día</th>
                    <th>Horario</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($horarios as $horario)
                    <tr>
                        <td>{{ $horario->espacio->nombre }}</td>
                        <td>{{ $dias[$horario->dia_semana] }}</td>
                        <td>{{ substr($horario->hora_inicio, 0, 5) }}–{{ substr($horario->hora_fin, 0, 5) }}</td>
                        <td>
                            <span class="badge text-bg-{{ $horario->activo ? 'success' : 'secondary' }}">
                                {{ $horario->activo ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" wire:click="alternarActivo({{ $horario->id }})">
                                {{ $horario->activo ? 'Desactivar' : 'Activar' }}
                            </button>
                            <button class="btn btn-sm btn-outline-danger" wire:click="eliminar({{ $horario->id }})" wire:confirm="¿Eliminar esta franja horaria?">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Todavía no hay franjas horarias configuradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
