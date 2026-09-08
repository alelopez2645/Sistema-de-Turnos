<div>
    <div class="mb-4" style="max-width: 380px;">
        <label class="form-label small">¿Qué datos considerar?</label>
        <select class="form-select form-select-sm" wire:model.live="alcance">
            <option value="todos">Todas las solicitudes (pendientes + aprobadas)</option>
            <option value="aprobado">Solo las aprobadas (uso real confirmado)</option>
        </select>
        <div class="form-text">Las canceladas nunca se cuentan en los gráficos de abajo.</div>
    </div>

    @if (!empty($insights))
        <div class="card mb-4" style="border-color: var(--ies-primary);">
            <div class="card-body">
                <h2 class="h6">En resumen</h2>
                <ul class="mb-0 small">
                    @foreach ($insights as $frase)
                        <li>{{ $frase }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-md">
            <div class="card text-center h-100"><div class="card-body">
                <div class="fs-3 fw-bold">{{ $totales['total'] }}</div>
                <div class="small text-muted">Turnos totales</div>
            </div></div>
        </div>
        <div class="col-6 col-md">
            <div class="card text-center h-100"><div class="card-body">
                <div class="fs-3 fw-bold text-warning">{{ $totales['pendientes'] }}</div>
                <div class="small text-muted">Pendientes de revisar</div>
            </div></div>
        </div>
        <div class="col-6 col-md">
            <div class="card text-center h-100"><div class="card-body">
                <div class="fs-3 fw-bold text-success">{{ $totales['aprobados'] }}</div>
                <div class="small text-muted">Aprobados</div>
            </div></div>
        </div>
        <div class="col-6 col-md">
            <div class="card text-center h-100"><div class="card-body">
                <div class="fs-3 fw-bold text-danger">{{ $totales['rechazados'] }}</div>
                <div class="small text-muted">Rechazados</div>
            </div></div>
        </div>
        <div class="col-6 col-md">
            <div class="card text-center h-100"><div class="card-body">
                <div class="fs-3 fw-bold text-secondary">{{ $totales['cancelados'] }}</div>
                <div class="small text-muted">Cancelados</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Turnos por espacio</h2>
                <p class="small text-muted">Qué tan seguido se reserva cada espacio o equipo.</p>
                @forelse ($porEspacio as $fila)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $fila->espacio }}</span>
                            <span class="text-muted">{{ $fila->cantidad }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="width: {{ $fila->cantidad / $maxEspacio * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="small text-muted mb-0">Todavía no hay turnos registrados.</p>
                @endforelse
            </div></div>
        </div>

        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Turnos por franja horaria</h2>
                <p class="small text-muted">A qué hora del día empiezan más turnos (útil para detectar los momentos de mayor demanda).</p>
                @forelse ($porHora as $fila)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $fila->hora }}:00 hs</span>
                            <span class="text-muted">{{ $fila->cantidad }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: {{ $fila->cantidad / $maxPorHora * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="small text-muted mb-0">Todavía no hay turnos registrados.</p>
                @endforelse
            </div></div>
        </div>

        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Turnos por día de la semana</h2>
                <p class="small text-muted">No se muestran sábados ni domingos porque no están habilitados para reservas.</p>
                @foreach ($porDia as $fila)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $fila['dia'] }}</span>
                            <span class="text-muted">{{ $fila['cantidad'] }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-secondary" style="width: {{ $fila['cantidad'] / $maxPorDia * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div></div>
        </div>

        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Uso de la Sala de Informática</h2>
                <p class="small text-muted mb-1">Promedio de computadoras solicitadas por turno, sobre un total de 32 PCs.</p>
                <div class="fs-3 fw-bold">{{ $promedioComputadoras ? number_format($promedioComputadoras, 1) : '—' }}</div>
            </div></div>
        </div>

        <div class="col-12">
            <div class="card"><div class="card-body">
                <h2 class="h6">Qué carrera usa más cada espacio</h2>
                <p class="small text-muted">Las 10 combinaciones de carrera + espacio con más turnos.</p>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr class="small text-muted">
                                <th>Carrera</th>
                                <th>Espacio</th>
                                <th>Turnos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($porCarreraEspacio as $fila)
                                <tr>
                                    <td>{{ $fila->carrera }}</td>
                                    <td>{{ $fila->espacio }}</td>
                                    <td>{{ $fila->cantidad }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted small">Todavía no hay datos suficientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
    </div>
</div>
