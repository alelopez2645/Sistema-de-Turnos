@extends('layouts.app')

@section('titulo', 'Espacios y Equipos')

@section('content')
    <div class="ies-page-header">
        <h1 class="h4 mb-0">Espacios y Equipos</h1>
        <p>Ajustá capacidad, cantidad de unidades disponibles y equipamiento de cada espacio.</p>
    </div>

    @livewire('gestion-espacios')
@endsection
