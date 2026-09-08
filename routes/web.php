<?php

use App\Http\Controllers\Auth\LoginController;
use App\Models\Turno;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrarFormulario'])->name('login');
    Route::post('/login', [LoginController::class, 'iniciarSesion']);
});

Route::post('/logout', [LoginController::class, 'cerrarSesion'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    Route::get('/turnos/auditorio', fn () => view('turnos.solicitar', [
        'tipo' => 'auditorio',
        'titulo' => 'Auditorio',
    ]))->name('turnos.auditorio');

    Route::get('/turnos/informatica', fn () => view('turnos.solicitar', [
        'tipo' => 'sala_informatica',
        'titulo' => 'Sala de Informática',
    ]))->name('turnos.informatica');

    Route::get('/turnos/capacitacion', fn () => view('turnos.solicitar', [
        'tipo' => 'sala_capacitacion',
        'titulo' => 'Sala de Capacitación',
    ]))->name('turnos.capacitacion');

    Route::get('/turnos/tv-smart', fn () => view('turnos.solicitar', [
        'tipo' => 'tv_smart',
        'titulo' => 'TV Smart',
    ]))->name('turnos.tv-smart');

    Route::get('/turnos/proyector', fn () => view('turnos.solicitar', [
        'tipo' => 'proyector',
        'titulo' => 'Proyector',
    ]))->name('turnos.proyector');

    Route::get('/turnos/{turno}/editar', fn (Turno $turno) => view('turnos.editar', [
        'turno' => $turno,
    ]))->name('turnos.editar');

    Route::get('/mis-turnos', fn () => view('turnos.mis-turnos'))->name('turnos.mis-turnos');

    Route::middleware('es.administrador')->group(function () {
        Route::get('/admin/turnos', fn () => view('admin.panel'))->name('admin.turnos');
        Route::get('/admin/dashboard', fn () => view('admin.dashboard'))->name('admin.dashboard');
        Route::get('/admin/espacios', fn () => view('admin.espacios'))->name('admin.espacios');
        Route::get('/admin/disponibilidad', fn () => view('admin.disponibilidad'))->name('admin.disponibilidad');
        Route::get('/admin/usuarios', fn () => view('admin.usuarios'))->name('admin.usuarios');
    });
});
