<div class="ies-notif-bell" x-data="{ abierto: false }" @click.outside="abierto = false" wire:poll.60s>
    <button type="button" class="ies-notif-btn" @click="abierto = !abierto" aria-label="Notificaciones">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 3C9.79 3 8 4.79 8 7v3.09c0 .55-.16 1.09-.46 1.55L6.2 13.6c-.94 1.44.09 3.35 1.8 3.35h7.99c1.71 0 2.75-1.91 1.8-3.35l-1.34-1.96A2.99 2.99 0 0 1 16 10.09V7c0-2.21-1.79-4-4-4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            <path d="M9.5 19a2.5 2.5 0 0 0 5 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
        </svg>
        @if ($noLeidas > 0)
            <span class="ies-notif-badge">{{ $noLeidas > 9 ? '9+' : $noLeidas }}</span>
        @endif
    </button>

    <div class="ies-notif-dropdown" x-show="abierto" x-transition x-cloak @click="abierto = false">
        <div class="ies-notif-header">
            <span>Notificaciones</span>
            @if ($noLeidas > 0)
                <button type="button" class="ies-notif-marcar-todas" wire:click.stop="marcarTodasLeidas">Marcar todas leídas</button>
            @endif
        </div>

        <div class="ies-notif-lista">
            @forelse ($notificaciones as $n)
                <a
                    href="{{ auth()->user()->esAdministrador() ? route('admin.turnos') : route('turnos.mis-turnos') }}"
                    class="ies-notif-item @if (!$n->read_at) no-leida @endif"
                    wire:click.stop="marcarLeida('{{ $n->id }}')"
                >
                    <div class="ies-notif-item-titulo">{{ $n->data['titulo'] ?? 'Actualización' }}</div>
                    <div class="ies-notif-item-texto">
                        {{ $n->data['mensaje'] ?? '' }}
                        @if (!empty($n->data['espacio']))
                            — {{ $n->data['espacio'] }} · {{ $n->data['fecha'] }} {{ $n->data['horario'] }}
                        @endif
                    </div>
                    <div class="ies-notif-item-fecha">{{ $n->created_at->diffForHumans() }}</div>
                </a>
            @empty
                <div class="ies-notif-vacio">Todavía no tenés notificaciones.</div>
            @endforelse
        </div>
    </div>
</div>
