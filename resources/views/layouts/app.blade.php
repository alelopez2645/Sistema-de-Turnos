<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Turnos') — IES Nuevo Horizonte</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --ies-primary: #1B4F5C;
            --ies-primary-rgb: 27, 79, 92;
            --ies-primary-dark: #123640;
            --ies-accent: #C1652F;
            --ies-accent-rgb: 193, 101, 47;
            --ies-slate: #55636B;
            --ies-slate-rgb: 85, 99, 107;
            --ies-bg: #F3F5F6;
            --ies-surface: #FFFFFF;
            --ies-border: #E1E6E8;
            --ies-text: #202B30;
            --ies-text-muted: #62717A;

            --bs-primary: var(--ies-primary);
            --bs-primary-rgb: var(--ies-primary-rgb);
            --bs-secondary: var(--ies-slate);
            --bs-secondary-rgb: var(--ies-slate-rgb);
            --bs-link-color: var(--ies-primary);
            --bs-link-color-rgb: var(--ies-primary-rgb);
            --bs-link-hover-color: var(--ies-primary-dark);
            --bs-body-bg: var(--ies-bg);
            --bs-body-color: var(--ies-text);
            --bs-border-color: var(--ies-border);
            --bs-border-radius: .5rem;
            --bs-border-radius-sm: .375rem;
            --bs-border-radius-lg: .75rem;
            --bs-body-font-family: 'Manrope', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
        }

        body {
            font-family: var(--bs-body-font-family);
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, h5, h6, .card-title {
            font-weight: 700;
            letter-spacing: -.01em;
            color: var(--ies-text);
        }

        a { text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Estructura general: sidebar fijo + contenido */
        .ies-shell {
            display: flex;
            min-height: 100vh;
            align-items: stretch;
        }
        .ies-sidebar {
            width: 250px;
            flex-shrink: 0;
            background: linear-gradient(180deg, var(--ies-primary) 0%, var(--ies-primary-dark) 100%);
            border-right: 3px solid var(--ies-accent);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .ies-sidebar-brand {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.14);
        }
        .ies-sidebar-brand a {
            display: flex;
            align-items: center;
            gap: .65rem;
            color: #fff;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: -.01em;
        }
        .ies-sidebar-brand a:hover { text-decoration: none; }
        .ies-sidebar-brand img {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
        }
        .ies-sidebar-brand small {
            display: block;
            font-weight: 500;
            font-size: .7rem;
            color: rgba(255,255,255,.6);
        }
        .ies-sidebar-nav {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: .85rem .75rem;
        }
        .ies-nav-link, .ies-nav-sublink {
            display: block;
            padding: .55rem .85rem;
            border-radius: .4rem;
            color: rgba(255,255,255,.85);
            font-weight: 600;
            font-size: .9rem;
            margin-bottom: .15rem;
        }
        .ies-nav-link:hover, .ies-nav-sublink:hover {
            background: rgba(255,255,255,.09);
            color: #fff;
            text-decoration: none;
        }
        .ies-nav-link.active, .ies-nav-sublink.active {
            background: rgba(255,255,255,.16);
            color: #fff;
            box-shadow: inset 3px 0 0 var(--ies-accent);
        }
        .ies-nav-group { margin-top: .35rem; }
        .ies-nav-group-label {
            padding: .8rem .85rem .3rem;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: rgba(255,255,255,.5);
            font-weight: 700;
        }
        .ies-nav-sublink {
            padding-left: 1.5rem;
            font-size: .85rem;
            font-weight: 500;
        }
        .ies-sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,.14);
        }
        .ies-content {
            flex: 1 1 auto;
            min-width: 0;
        }

        @media (max-width: 768px) {
            .ies-shell { flex-direction: column; }
            .ies-sidebar {
                width: 100%;
                height: auto;
                position: static;
                flex-direction: row;
                flex-wrap: wrap;
                align-items: center;
                border-right: none;
                border-bottom: 3px solid var(--ies-accent);
            }
            .ies-sidebar-brand { border-bottom: none; padding: .75rem 1rem; }
            .ies-sidebar-nav {
                display: flex;
                flex-wrap: wrap;
                gap: .15rem;
                padding: .5rem .75rem;
                flex: 1 1 100%;
            }
            .ies-nav-group { display: flex; flex-wrap: wrap; align-items: center; margin: 0; }
            .ies-nav-group-label { padding: .4rem .5rem; }
            .ies-sidebar-footer {
                border-top: none;
                padding: .5rem 1rem 1rem;
                flex: 1 1 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .ies-sidebar-footer form { width: auto; }
        }

        /* Tarjetas: más planas, borde suave, sin sombra genérica de "kit SaaS" */
        .card {
            border: 1px solid var(--ies-border);
            border-radius: var(--bs-border-radius);
            box-shadow: none;
        }
        .card-body h2.h6 {
            margin-bottom: .9rem;
            font-size: .95rem;
        }
        .card-espacio {
            transition: border-color .15s ease, transform .15s ease;
            overflow: hidden;
        }
        .card-espacio:hover {
            border-color: var(--ies-primary);
            transform: translateY(-2px);
        }
        .card-espacio-img {
            height: 160px;
            object-fit: cover;
            background-color: #eef1f5;
        }
        .card-espacio-img-contain {
            object-fit: contain;
            padding: 1.25rem;
        }
        /* Calendario semanal de reserva (drag para elegir día + horario) */
        .calendario-semanal-wrap {
            overflow: auto;
            max-height: 640px;
            border: 1px solid var(--ies-border);
            border-radius: var(--bs-border-radius);
            background: var(--ies-surface);
        }
        .calendario-semanal {
            display: grid;
            grid-template-columns: 64px repeat(7, minmax(108px, 1fr));
            min-width: 860px;
            user-select: none;
        }
        .fila-header, .fila-horario {
            display: contents;
        }
        .celda-esquina, .celda-dia-header {
            position: sticky;
            top: 0;
            background: var(--ies-surface);
            z-index: 3;
            border-bottom: 1px solid var(--ies-border);
            padding: .4rem .25rem;
            text-align: center;
        }
        .celda-esquina {
            left: 0;
            z-index: 4;
        }
        .celda-dia-header.es-hoy {
            color: var(--ies-primary);
            font-weight: 800;
        }
        .celda-dia-header.es-finde {
            color: var(--ies-text-muted);
            background: #F5F6F7;
        }
        .dia-nombre {
            display: block;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .dia-fecha {
            display: block;
            font-size: .85rem;
        }
        .celda-hora {
            position: sticky;
            left: 0;
            z-index: 1;
            background: var(--ies-surface);
            font-size: .68rem;
            color: var(--ies-text-muted);
            text-align: right;
            padding: 0 .4rem;
            border-right: 1px solid var(--ies-border);
            border-top: 1px solid var(--ies-border);
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            min-height: 30px;
        }
        .celda-hora.hora-en-punto {
            font-weight: 700;
            color: var(--ies-text);
        }
        .celda-turno {
            min-height: 30px;
            border-top: 1px solid var(--ies-border);
            border-left: 1px solid var(--ies-border);
            position: relative;
            cursor: pointer;
            background: #fff;
        }
        .calendario-semanal > .fila-horario:last-child .celda-turno,
        .calendario-semanal > .fila-horario:last-child .celda-hora {
            border-bottom: 1px solid var(--ies-border);
        }
        .celda-turno.celda-fuera {
            background: repeating-linear-gradient(45deg, #F3F4F6, #F3F4F6 6px, #ECEEF0 6px, #ECEEF0 12px);
            cursor: not-allowed;
        }
        .celda-turno.celda-pasado {
            background: #F3F4F6;
            cursor: not-allowed;
        }
        .celda-turno.celda-bloqueada {
            cursor: not-allowed;
        }
        .celda-turno:not(.celda-bloqueada):hover {
            background: rgba(var(--ies-primary-rgb), .12);
        }
        .celda-turno.celda-en-arrastre {
            background: rgba(var(--ies-accent-rgb), .55) !important;
        }

        /* Bloques de reserva: uno por turno, ocupando todas las filas de su
           horario (grid-row: span) con su contenido adentro. Van dibujados
           encima de la grilla de celdas (mismo grid-column/grid-row) pero no
           reciben clics ni arrastre (pointer-events: none), así la celda de
           abajo sigue funcionando para seleccionar el resto del horario libre. */
        .evento-turno {
            position: relative;
            z-index: 2;
            margin-top: 1px;
            border-radius: 4px;
            padding: .2rem .35rem;
            font-size: .64rem;
            line-height: 1.25;
            overflow: hidden;
            color: #fff;
            pointer-events: none;
        }
        .evento-turno.evento-aprobado {
            background: var(--ies-primary);
        }
        .evento-turno.evento-pendiente {
            background: var(--ies-accent);
        }
        .evento-turno.evento-propio {
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .85);
        }
        .evento-horario {
            display: block;
            font-weight: 700;
        }
        .evento-titulo {
            display: block;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .evento-meta {
            display: block;
            opacity: .9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .calendario-leyenda .leyenda-dot {
            display: inline-block;
            width: .7rem;
            height: .7rem;
            border-radius: 2px;
            margin-right: .25rem;
            vertical-align: middle;
        }
        .leyenda-libre { background: #fff; border: 1px solid var(--ies-border); }
        .leyenda-pendiente { background: var(--ies-accent); }
        .leyenda-aprobado { background: var(--ies-primary); }
        .leyenda-propia { background: var(--ies-primary); box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .85); }
        .leyenda-bloqueada { background: repeating-linear-gradient(45deg, #F3F4F6, #F3F4F6 4px, #ECEEF0 4px, #ECEEF0 8px); border: 1px solid var(--ies-border); }

        .btn-ies-accent {
            background: var(--ies-accent);
            border-color: var(--ies-accent);
            color: #fff;
        }
        .btn-ies-accent:hover,
        .btn-ies-accent:focus {
            background: #A3541F;
            border-color: #A3541F;
            color: #fff;
        }

        .card-espacio-img-placeholder {
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: .5rem 1rem;
            background: linear-gradient(135deg, var(--ies-primary) 0%, var(--ies-primary-dark) 100%);
            color: #fff;
            font-weight: 600;
            font-size: .95rem;
        }

        /* Botones */
        .btn {
            font-weight: 600;
            border-radius: var(--bs-border-radius-sm);
        }
        .btn-primary {
            --bs-btn-hover-bg: var(--ies-primary-dark);
            --bs-btn-hover-border-color: var(--ies-primary-dark);
            --bs-btn-active-bg: var(--ies-primary-dark);
            --bs-btn-active-border-color: var(--ies-primary-dark);
        }

        /* Formularios */
        .form-label {
            font-weight: 600;
            color: var(--ies-text-muted);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--ies-primary);
            box-shadow: 0 0 0 .2rem rgba(var(--ies-primary-rgb), .15);
        }

        /* Tablas */
        .table > thead {
            border-bottom: 2px solid var(--ies-border);
        }
        .table > :not(caption) > * > * {
            padding: .65rem .75rem;
        }

        /* Encabezado de página estándar */
        .ies-page-header {
            margin-bottom: 1.5rem;
        }
        .ies-page-header p {
            color: var(--ies-text-muted);
            font-size: .9rem;
            margin: .15rem 0 0;
        }

        /* Calendario de selección de fecha (turnos) */
        .ies-calendario {
            border: 1px solid var(--ies-border);
            border-radius: var(--bs-border-radius);
            padding: .85rem;
            background: var(--ies-surface);
            max-width: 320px;
        }
        .ies-calendario.is-invalid {
            border-color: #dc3545;
        }
        .ies-calendario-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .6rem;
        }
        .ies-calendario-mes {
            font-weight: 700;
            font-size: .9rem;
            text-transform: capitalize;
            color: var(--ies-text);
        }
        .ies-calendario-nav {
            border: 1px solid var(--ies-border);
            background: var(--ies-bg);
            color: var(--ies-text);
            border-radius: .35rem;
            width: 28px;
            height: 28px;
            line-height: 1;
            font-weight: 700;
            cursor: pointer;
        }
        .ies-calendario-nav:hover {
            background: var(--ies-primary);
            color: #fff;
            border-color: var(--ies-primary);
        }
        .ies-calendario-semana {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-size: .7rem;
            font-weight: 700;
            color: var(--ies-text-muted);
            margin-bottom: .3rem;
        }
        .ies-calendario-grilla {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: .2rem;
            transition: opacity .1s ease;
        }
        .ies-calendario-cargando { opacity: .5; }
        .ies-calendario-dia {
            aspect-ratio: 1 / 1;
            border: 1px solid transparent;
            background: transparent;
            border-radius: .35rem;
            font-size: .82rem;
            font-weight: 600;
            color: var(--ies-text);
            cursor: pointer;
        }
        .ies-calendario-dia:hover:not(:disabled) {
            background: rgba(var(--ies-primary-rgb), .1);
            border-color: var(--ies-primary);
        }
        .ies-calendario-dia:disabled {
            color: var(--ies-text-muted);
            opacity: .35;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        .ies-calendario-dia--fuera {
            color: var(--ies-text-muted);
            opacity: .5;
        }
        .ies-calendario-dia--hoy {
            border-color: var(--ies-accent);
        }
        .ies-calendario-dia--seleccionado {
            background: var(--ies-primary);
            border-color: var(--ies-primary);
            color: #fff;
        }
        .ies-calendario-dia--seleccionado:hover {
            background: var(--ies-primary-dark);
        }
        .ies-calendario-leyenda {
            display: flex;
            gap: 1rem;
            margin-top: .65rem;
            font-size: .72rem;
            color: var(--ies-text-muted);
        }
        .ies-calendario-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: .25rem;
        }
        .ies-calendario-dot--disponible { background: var(--ies-primary); }
        .ies-calendario-dot--bloqueado { background: var(--ies-border); }

        /* Barras simples para el dashboard (sin dependencias JS) */
        .progress {
            background-color: var(--ies-bg);
            border-radius: 999px;
        }
        .progress-bar {
            background-color: var(--ies-primary);
            border-radius: 999px;
        }
    </style>

    @livewireStyles
</head>
<body>
    <div class="ies-shell">
        <aside class="ies-sidebar">
            <div class="ies-sidebar-brand">
                <a href="{{ auth()->check() && auth()->user()->esAdministrador() ? route('admin.dashboard') : route('dashboard') }}">
                    <img src="{{ asset('images/logo-ies.png') }}" alt="Logo IES Nuevo Horizonte">
                    <span>
                        IES Nuevo Horizonte
                        <small>Sistema de turnos</small>
                    </span>
                </a>
            </div>

            @include('partials.sidebar')
        </aside>

        <div class="ies-content">
            <main class="container-fluid py-4 px-4">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
