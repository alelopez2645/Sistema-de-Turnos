@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('content')
    <div class="ies-page-header">
        <h1 class="h4 mb-0">Dashboard</h1>
        <p>Un resumen de cómo se están usando los espacios y equipos del instituto.</p>
    </div>

    @livewire('admin-dashboard')
@endsection
