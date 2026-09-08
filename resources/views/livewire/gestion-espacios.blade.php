<div>
    @if ($mensaje)
        <div class="alert alert-success py-2 small">{{ $mensaje }}</div>
    @endif

    <div class="d-flex flex-column gap-3">
        @foreach ($espacios as $espacio)
            <div class="card">
                <div class="card-body">
                    <h2 class="h6">{{ $espacio->nombre }}</h2>

                    <div class="row g-3">
                        @php
                            $esMovil = in_array($espacio->tipo, [\App\Enums\TipoEspacio::TV_SMART, \App\Enums\TipoEspacio::PROYECTOR]);
                        @endphp
                        @unless ($esMovil)
                            <div class="col-md-3">
                                <label class="form-label small">Capacidad</label>
                                <input type="number" min="1" class="form-control form-control-sm @error('capacidad.' . $espacio->id) is-invalid @enderror" wire:model="capacidad.{{ $espacio->id }}">
                                @error('capacidad.' . $espacio->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endunless

                        <div class="col-md-3">
                            <label class="form-label small">
                                {{ $espacio->tipo === \App\Enums\TipoEspacio::TV_SMART ? 'Cantidad de televisores' : ($espacio->tipo === \App\Enums\TipoEspacio::PROYECTOR ? 'Cantidad de proyectores' : 'Unidades disponibles') }}
                            </label>
                            <input type="number" min="1" class="form-control form-control-sm @error('unidadesDisponibles.' . $espacio->id) is-invalid @enderror" wire:model="unidadesDisponibles.{{ $espacio->id }}">
                            @error('unidadesDisponibles.' . $espacio->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Cantidad máxima de reservas simultáneas permitidas para este espacio.</div>
                        </div>

                        <div class="col-md-{{ $esMovil ? 9 : 6 }}">
                            <label class="form-label small">Equipamiento</label>
                            <input type="text" class="form-control form-control-sm" wire:model="equipamiento.{{ $espacio->id }}">
                        </div>
                    </div>

                    <button class="btn btn-sm btn-primary mt-3" wire:click="guardar({{ $espacio->id }})">Guardar</button>
                </div>
            </div>
        @endforeach
    </div>
</div>
