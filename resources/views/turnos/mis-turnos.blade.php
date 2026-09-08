@extends('layouts.app')

@section('titulo', 'Mis turnos')

@section('content')
    <div class="ies-page-header">
        <h1 class="h4 mb-0">Mis turnos</h1>
        <p>Consultá el estado de tus solicitudes, editalas o cancelalas.</p>
    </div>

    @livewire('mis-turnos')
@endsection
