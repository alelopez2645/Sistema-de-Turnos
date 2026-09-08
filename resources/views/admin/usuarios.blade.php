@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('content')
    <div class="ies-page-header">
        <h1 class="h4 mb-0">Usuarios</h1>
        <p>Dá de alta, editá o dá de baja a docentes y administradores, o importalos desde un Excel.</p>
    </div>

    <div class="mb-4">
        @livewire('importar-docentes')
    </div>

    @livewire('gestion-usuarios')
@endsection
