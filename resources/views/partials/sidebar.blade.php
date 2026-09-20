@auth
    @php
        $ies_esAdmin = auth()->user()->esAdministrador();
        $ies_esDocente = auth()->user()->esDocente();
    @endphp

    <nav class="ies-sidebar-nav">
        @if ($ies_esAdmin)
            <a href="{{ route('admin.dashboard') }}" class="ies-nav-link @if (request()->routeIs('admin.dashboard')) active @endif">
                Dashboard
            </a>
        @endif

        @if ($ies_esDocente || $ies_esAdmin)
            <a href="{{ route('dashboard') }}" class="ies-nav-link @if (request()->routeIs('dashboard')) active @endif">
                Solicitar Turnos
            </a>
            <a href="{{ route('turnos.mis-turnos') }}" class="ies-nav-link @if (request()->routeIs('turnos.mis-turnos')) active @endif">
                Mis turnos
            </a>
        @endif

        @if ($ies_esAdmin)
            <a href="{{ route('admin.turnos') }}" class="ies-nav-link @if (request()->routeIs('admin.turnos')) active @endif">
                Aprobar Turnos
            </a>

            <div class="ies-nav-group">
                <div class="ies-nav-group-label">Administración</div>
                <a href="{{ route('admin.usuarios') }}" class="ies-nav-sublink @if (request()->routeIs('admin.usuarios')) active @endif">
                    Usuarios
                </a>
                <a href="{{ route('admin.espacios') }}" class="ies-nav-sublink @if (request()->routeIs('admin.espacios')) active @endif">
                    Espacios y Equipos
                </a>
                <a href="{{ route('admin.disponibilidad') }}" class="ies-nav-sublink @if (request()->routeIs('admin.disponibilidad')) active @endif">
                    Horarios y carreras
                </a>
            </div>
        @endif
    </nav>

    <div class="ies-sidebar-footer">
        <div class="small text-white-50 text-truncate">{{ auth()->user()->name }}</div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button class="btn btn-sm btn-outline-light w-100" type="submit">Salir</button>
        </form>
    </div>
@endauth
