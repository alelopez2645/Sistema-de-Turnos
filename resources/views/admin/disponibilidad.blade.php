@extends('layouts.app')

@section('titulo', 'Horarios y carreras')

@section('content')
    <div class="ies-page-header">
        <h1 class="h4 mb-0">Horarios y carreras</h1>
        <p>Definí en qué días y franjas horarias se puede solicitar cada espacio, y administrá las carreras del instituto.</p>
    </div>

    <h2 class="h6 mb-3">Horarios disponibles por espacio</h2>
    <div class="mb-5">
        @livewire('gestion-disponibilidad')
    </div>

    <h2 class="h6 mb-3">Carreras</h2>
    @livewire('gestion-carreras')
@endsection
