<div class="card">
    <div class="card-body">
        <h2 class="h6">{{ $editandoId ? 'Editar carrera' : 'Nueva carrera' }}</h2>
        <form wire:submit="guardar" class="d-flex gap-2 align-items-start flex-wrap">
            <div class="flex-grow-1" style="min-width: 240px;">
                <input type="text" class="form-control form-control-sm @error('nombre') is-invalid @enderror" wire:model="nombre" placeholder="Nombre de la carrera">
                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-sm btn-primary">{{ $editandoId ? 'Guardar cambios' : 'Crear carrera' }}</button>
            @if ($editandoId)
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelarEdicion">Cancelar</button>
            @endif
        </form>

        @if ($mensaje)
            <div class="alert alert-info py-2 small mt-3 mb-0">{{ $mensaje }}</div>
        @endif

        <div class="table-responsive mt-3">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr class="small text-muted">
                        <th>Carrera</th>
                        <th>Docentes</th>
                        <th>Turnos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($carreras as $carrera)
                        <tr>
                            <td>{{ $carrera->nombre }}</td>
                            <td>{{ $carrera->docentes_count }}</td>
                            <td>{{ $carrera->turnos_count }}</td>
                            <td class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" wire:click="editar({{ $carrera->id }})">Editar</button>
                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    wire:click="eliminar({{ $carrera->id }})"
                                    wire:confirm="¿Eliminar esta carrera? Los docentes y turnos que la tengan asignada quedarán sin carrera."
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted small">Todavía no hay carreras cargadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
