@extends('layouts.app')

@section('titulo', 'Aprobar Turnos')

@section('content')
    <div class="ies-page-header">
        <h1 class="h4 mb-0">Aprobar Turnos</h1>
        <p>Revisá, aprobá, rechazá o cancelá las solicitudes de turno de los docentes.</p>
    </div>

    @livewire('panel-aprobacion')
@endsection
