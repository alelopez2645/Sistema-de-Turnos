@extends('layouts.app')

@section('titulo', 'Editar turno')

@section('content')
    <div class="ies-page-header">
        <h1 class="h4 mb-0">Editar turno — {{ $turno->espacio->nombre }}</h1>
        <p>Los cambios respetan las mismas reglas que una solicitud nueva.</p>
    </div>

    @livewire('editar-turno', ['turno' => $turno])
@endsection
