<?php

namespace App\Notifications;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica por correo al docente cada vez que su turno se crea, modifica,
 * aprueba, rechaza o cancela.
 *
 * Nota: esta clase no implementa ShouldQueue, así que el envío es síncrono.
 * Si en producción el envío de mail empieza a demorar la respuesta al
 * usuario, alcanza con agregar "implements ShouldQueue" a la clase y
 * configurar un queue worker (Laragon corre bien con QUEUE_CONNECTION=database).
 */
class TurnoNotification extends Notification
{
    use Queueable;

    public const EVENTO_CREADO = 'creado';
    public const EVENTO_MODIFICADO = 'modificado';
    public const EVENTO_APROBADO = 'aprobado';
    public const EVENTO_RECHAZADO = 'rechazado';
    public const EVENTO_CANCELADO = 'cancelado';

    public function __construct(
        public Turno $turno,
        public string $evento,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $turno = $this->turno;
        $horario = substr($turno->hora_inicio, 0, 5) . ' a ' . substr($turno->hora_fin, 0, 5);

        $mensaje = (new MailMessage)
            ->subject($this->asunto())
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line($this->introduccion())
            ->line('Espacio: ' . $turno->espacio->nombre)
            ->line('Fecha: ' . $turno->fecha->format('d/m/Y'))
            ->line('Horario: ' . $horario);

        if ($this->evento === self::EVENTO_RECHAZADO && $turno->observaciones) {
            $mensaje->line('Motivo: ' . $turno->observaciones);
        }

        if ($this->evento === self::EVENTO_APROBADO && $turno->observaciones) {
            $mensaje->line('Observaciones de administración: ' . $turno->observaciones);
        }

        return $mensaje
            ->line('Ante cualquier duda, comunicate con administración del instituto.')
            ->salutation('IES Nuevo Horizonte — Sistema de Turnos');
    }

    protected function asunto(): string
    {
        return match ($this->evento) {
            self::EVENTO_CREADO => "Turno solicitado — {$this->turno->espacio->nombre}",
            self::EVENTO_MODIFICADO => "Turno modificado — {$this->turno->espacio->nombre}",
            self::EVENTO_APROBADO => "Turno aprobado — {$this->turno->espacio->nombre}",
            self::EVENTO_RECHAZADO => "Turno rechazado — {$this->turno->espacio->nombre}",
            self::EVENTO_CANCELADO => "Turno cancelado — {$this->turno->espacio->nombre}",
            default => 'Actualización de tu turno',
        };
    }

    protected function introduccion(): string
    {
        return match ($this->evento) {
            self::EVENTO_CREADO => 'Registramos tu solicitud de turno. Queda pendiente de aprobación por administración.',
            self::EVENTO_MODIFICADO => 'Se modificó tu turno. Si estaba aprobado, vuelve a quedar pendiente de revisión.',
            self::EVENTO_APROBADO => 'Tu turno fue aprobado.',
            self::EVENTO_RECHAZADO => 'Tu turno fue rechazado.',
            self::EVENTO_CANCELADO => 'Tu turno fue cancelado.',
            default => 'Hubo una actualización en tu turno.',
        };
    }
}
