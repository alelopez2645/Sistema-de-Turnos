@extends('layouts.app')

@section('titulo', $titulo)

@section('content')
    <div class="ies-page-header">
        <h1 class="h4 mb-0">{{ $titulo }}</h1>
        <p>Completá el formulario para solicitar un turno.</p>
    </div>

    @livewire('formulario-turno', ['tipoEspacio' => $tipo])
@endsection
