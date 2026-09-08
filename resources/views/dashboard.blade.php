@extends('layouts.app')

@section('titulo', 'Solicitar Turnos')

@section('content')
    <div class="ies-page-header">
        <h1 class="h4 mb-0">Hola, {{ auth()->user()->name }}</h1>
        <p>Elegí el espacio o equipo para el que querés solicitar un turno.</p>
    </div>

    <div class="row g-3">
        <div class="col-sm-6 col-lg">
            <div class="card h-100 card-espacio">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">Auditorio</h5>
                    <p class="card-text text-muted small flex-grow-1">Hasta 200 personas. Escenario, audio y pantalla.</p>
                    <a href="{{ route('turnos.auditorio') }}" class="btn btn-primary btn-sm">Solicitar turno</a>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg">
            <div class="card h-100 card-espacio">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">Sala de Informática</h5>
                    <p class="card-text text-muted small flex-grow-1">32 PCs de escritorio.</p>
                    <a href="{{ route('turnos.informatica') }}" class="btn btn-primary btn-sm">Solicitar turno</a>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg">
            <div class="card h-100 card-espacio">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">Sala de Capacitación</h5>
                    <p class="card-text text-muted small flex-grow-1">Hasta 80 personas. Encuentros y capacitaciones.</p>
                    <a href="{{ route('turnos.capacitacion') }}" class="btn btn-primary btn-sm">Solicitar turno</a>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg">
            <div class="card h-100 card-espacio">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">TV Smart</h5>
                    <p class="card-text text-muted small flex-grow-1">Préstamo por módulo de clase (40 min).</p>
                    <a href="{{ route('turnos.tv-smart') }}" class="btn btn-primary btn-sm">Solicitar turno</a>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg">
            <div class="card h-100 card-espacio">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">Proyector</h5>
                    <p class="card-text text-muted small flex-grow-1">Préstamo por módulo de clase (40 min).</p>
                    <a href="{{ route('turnos.proyector') }}" class="btn btn-primary btn-sm">Solicitar turno</a>
                </div>
            </div>
        </div>
    </div>
@endsection
