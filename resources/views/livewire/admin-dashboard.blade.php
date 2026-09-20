<div>
    <div class="mb-4" style="max-width: 380px;">
        <label class="form-label small">¿Qué datos considerar?</label>
        <select class="form-select form-select-sm" wire:model.live="alcance">
            <option value="todos">Todas las solicitudes (pendientes + aprobadas)</option>
            <option value="aprobado">Solo las aprobadas (uso real confirmado)</option>
        </select>
        <div class="form-text">Las canceladas nunca se cuentan en los gráficos de abajo. La tendencia semanal siempre muestra todas las solicitudes activas.</div>
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
        <div class="col-md-8">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Tendencia semanal</h2>
                <p class="small text-muted">Turnos solicitados por semana (últimas 8 semanas, lunes a domingo).</p>
                <div wire:ignore style="height: 220px;">
                    <canvas id="grafico-por-semana"></canvas>
                </div>
            </div></div>
        </div>

        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Estado de las solicitudes</h2>
                <p class="small text-muted">Pendientes, aprobados y rechazados (no incluye cancelados).</p>
                <div wire:ignore style="height: 220px;">
                    <canvas id="grafico-por-estado"></canvas>
                </div>
            </div></div>
        </div>

        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Turnos por espacio</h2>
                <p class="small text-muted">Qué tan seguido se reserva cada espacio o equipo.</p>
                <div wire:ignore style="height: 240px;">
                    <canvas id="grafico-por-espacio"></canvas>
                </div>
            </div></div>
        </div>

        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Turnos por franja horaria</h2>
                <p class="small text-muted">A qué hora del día empiezan más turnos (útil para detectar los momentos de mayor demanda).</p>
                <div wire:ignore style="height: 240px;">
                    <canvas id="grafico-por-hora"></canvas>
                </div>
            </div></div>
        </div>

        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Turnos por día de la semana</h2>
                <p class="small text-muted">No se muestran sábados ni domingos porque no están habilitados para reservas.</p>
                <div wire:ignore style="height: 220px;">
                    <canvas id="grafico-por-dia"></canvas>
                </div>
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

    @script
    <script>
        // Colores del tema institucional (los mismos que las variables CSS
        // --ies-primary / --ies-accent del layout).
        const colorPrimario = '#1B4F5C';
        const colorPrimarioClaro = 'rgba(27, 79, 92, .55)';
        const colorAccent = '#C1652F';
        const colorRechazado = '#B23A48';
        const colorGrilla = 'rgba(0, 0, 0, .06)';

        const opcionesComunes = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: colorGrilla } },
            },
        };

        const graficoEspacio = new Chart(document.getElementById('grafico-por-espacio'), {
            type: 'bar',
            data: {
                labels: @js($datosGraficos['porEspacio']['labels']),
                datasets: [{ data: @js($datosGraficos['porEspacio']['valores']), backgroundColor: colorPrimario, borderRadius: 4, maxBarThickness: 40 }],
            },
            options: opcionesComunes,
        });

        const graficoHora = new Chart(document.getElementById('grafico-por-hora'), {
            type: 'bar',
            data: {
                labels: @js($datosGraficos['porHora']['labels']),
                datasets: [{ data: @js($datosGraficos['porHora']['valores']), backgroundColor: colorAccent, borderRadius: 4, maxBarThickness: 30 }],
            },
            options: opcionesComunes,
        });

        const graficoDia = new Chart(document.getElementById('grafico-por-dia'), {
            type: 'bar',
            data: {
                labels: @js($datosGraficos['porDia']['labels']),
                datasets: [{ data: @js($datosGraficos['porDia']['valores']), backgroundColor: colorPrimarioClaro, borderRadius: 4, maxBarThickness: 40 }],
            },
            options: opcionesComunes,
        });

        const graficoSemana = new Chart(document.getElementById('grafico-por-semana'), {
            type: 'line',
            data: {
                labels: @js($datosGraficos['porSemana']['labels']),
                datasets: [{
                    data: @js($datosGraficos['porSemana']['valores']),
                    borderColor: colorPrimario,
                    backgroundColor: colorPrimarioClaro,
                    tension: .3,
                    fill: true,
                    pointRadius: 3,
                }],
            },
            options: opcionesComunes,
        });

        const graficoEstado = new Chart(document.getElementById('grafico-por-estado'), {
            type: 'doughnut',
            data: {
                labels: @js($datosGraficos['porEstado']['labels']),
                datasets: [{ data: @js($datosGraficos['porEstado']['valores']), backgroundColor: [colorAccent, colorPrimario, colorRechazado] }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            },
        });

        // Cuando cambia el filtro "todos/aprobado" (ver updatedAlcance() en
        // AdminDashboard.php), llegan los datos frescos acá para redibujar
        // los gráficos sin perder su instancia (evita el parpadeo de
        // destruir y crear el <canvas> de nuevo en cada cambio de filtro).
        $wire.on('dashboard-actualizado', ({ datos }) => {
            graficoEspacio.data.labels = datos.porEspacio.labels;
            graficoEspacio.data.datasets[0].data = datos.porEspacio.valores;
            graficoEspacio.update();

            graficoHora.data.labels = datos.porHora.labels;
            graficoHora.data.datasets[0].data = datos.porHora.valores;
            graficoHora.update();

            graficoDia.data.labels = datos.porDia.labels;
            graficoDia.data.datasets[0].data = datos.porDia.valores;
            graficoDia.update();

            graficoEstado.data.datasets[0].data = datos.porEstado.valores;
            graficoEstado.update();

            // graficoSemana no se toca: la tendencia semanal siempre
            // muestra todas las solicitudes activas, sin importar el filtro.
        });
    </script>
    @endscript
</div>
