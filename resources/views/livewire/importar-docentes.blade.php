<div class="card">
    <div class="card-body">
        <h2 class="h6">Importar docentes desde Excel</h2>
        <p class="small text-muted">
            El archivo debe tener una primera fila de encabezados con columnas de Nombre, Apellido y
            Correo electrónico (los nombres exactos pueden variar, por ejemplo "Email" o "Correo").
            Los docentes nuevos se crean con una contraseña temporal; los que ya existan por email
            solo actualizan su nombre.
        </p>

        @if ($resumen)
            <div class="alert alert-success py-2 small">{{ $resumen }}</div>
        @endif

        @if ($error)
            <div class="alert alert-danger py-2 small">{{ $error }}</div>
        @endif

        <form wire:submit="importar" class="d-flex gap-2 align-items-start flex-wrap">
            <div class="flex-grow-1" style="min-width: 240px;">
                <input type="file" accept=".xlsx,.xls,.csv" class="form-control form-control-sm @error('archivo') is-invalid @enderror" wire:model="archivo">
                @error('archivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-sm btn-primary" wire:loading.attr="disabled" wire:target="importar,archivo">
                <span wire:loading.remove wire:target="importar">Importar</span>
                <span wire:loading wire:target="importar">Procesando...</span>
            </button>
        </form>

        @if (!empty($resultados))
            <div class="alert alert-warning small mt-3 mb-2">
                Copiá o comunicá estas contraseñas ahora: no se vuelven a mostrar (en la base solo queda guardada su versión encriptada).
            </div>
            <div class="table-responsive">
                <table class="table table-sm bg-white">
                    <thead>
                        <tr class="small text-muted">
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Resultado</th>
                            <th>Contraseña temporal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultados as $fila)
                            <tr>
                                <td>{{ $fila['nombre'] }}</td>
                                <td>{{ $fila['email'] }}</td>
                                <td>{{ $fila['estado'] }}</td>
                                <td>
                                    @if ($fila['password'])
                                        <code>{{ $fila['password'] }}</code>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
