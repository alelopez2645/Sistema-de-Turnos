<?php

namespace App\Livewire;

use Livewire\Component;

class NotificacionesBell extends Component
{
    public function marcarLeida(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function marcarTodasLeidas(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.notificaciones-bell', [
            'notificaciones' => auth()->user()->notifications()->latest()->limit(8)->get(),
            'noLeidas' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
