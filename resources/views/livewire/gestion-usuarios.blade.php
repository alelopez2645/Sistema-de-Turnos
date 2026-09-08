<div>
    @if ($mensaje)
        <div class="alert alert-info py-2 small">{{ $mensaje }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">{{ $editandoId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
            <form wire:submit="guardar">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Nombre y apellido</label>
                        <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" wire:model="name">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small">Correo electrónico</label>
                        <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" wire:model="email">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small">
                            Contraseña {{ $editandoId ? '(dejar vacío para no cambiarla)' : '' }}
                        </label>
                        <input type="password" class="form-control form-control-sm @error('password') is-invalid @enderror" wire:model="password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Rol</label>
                        <select class="form-select form-select-sm @error('rol') is-invalid @enderror" wire:model.live="rol">
                            <option value="docente">Docente</option>
                            <option value="administrador">Administrador</option>
                        </select>
                        @error('rol') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if ($rol === 'docente')
                        <div class="col-md-4">
                            <label class="form-label small">Carrera</label>
                            <select class="form-select form-select-sm @error('carrera_id') is-invalid @enderror" wire:model="carrera_id">
                                <option value="">Seleccioná una carrera</option>
                                @foreach ($carreras as $carrera)
                                    <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                                @endforeach
                            </select>
                            @error('carrera_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-md-3">
                        <label class="form-label small">DNI (opcional)</label>
                        <input type="text" class="form-control form-control-sm @error('dni') is-invalid @enderror" wire:model="dni">
                        @error('dni') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small">Teléfono (opcional)</label>
                        <input type="text" class="form-control form-control-sm" wire:model="telefono">
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">{{ $editandoId ? 'Guardar cambios' : 'Crear usuario' }}</button>
                    @if ($editandoId)
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelarEdicion">Cancelar</button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="mb-3" style="max-width: 320px;">
        <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="busqueda" placeholder="Buscar por nombre o email...">
    </div>

    <div class="table-responsive">
        <table class="table table-sm bg-white align-middle">
            <thead>
                <tr class="small text-muted">
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Carrera</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr class="{{ !$usuario->activo ? 'text-muted' : '' }}">
                        <td>{{ $usuario->name }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td>{{ $usuario->rol === \App\Enums\RolUsuario::ADMINISTRADOR ? 'Administrador' : 'Docente' }}</td>
                        <td>{{ $usuario->carrera->nombre ?? '—' }}</td>
                        <td>
                            <span class="badge text-bg-{{ $usuario->activo ? 'success' : 'secondary' }}">
                                {{ $usuario->activo ? 'Activo' : 'Dado de baja' }}
                            </span>
                        </td>
                        <td class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" wire:click="editar({{ $usuario->id }})">Editar</button>
                            @if ($usuario->activo)
                                <button class="btn btn-sm btn-outline-danger" wire:click="darDeBaja({{ $usuario->id }})" wire:confirm="¿Dar de baja a este usuario? No podrá volver a iniciar sesión.">
                                    Dar de baja
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-success" wire:click="reactivar({{ $usuario->id }})">Reactivar</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No se encontraron usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $usuarios->links() }}</div>
</div>
