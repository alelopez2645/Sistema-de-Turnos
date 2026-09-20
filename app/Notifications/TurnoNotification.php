<?php

namespace App\Notifications;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

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

    /**
     * Content-ID fijo del logo institucional embebido en el mail (ver
     * toMail()). Al ir "adentro" del correo como adjunto en vez de cargarse
     * desde una URL, se ve siempre — incluso probando en local, donde
     * Gmail no puede llegar a http://127.0.0.1 o http://localhost.
     */
    protected const CID_LOGO = 'logo-ies@ies-nuevo-horizonte.local';

    public function __construct(
        public Turno $turno,
        public string $evento,
    ) {
    }

    /**
     * Punto único para disparar esta notificación. Si el envío de mail
     * falla (SMTP mal configurado, servidor caído, etc.) el error queda
     * registrado en los logs pero NO interrumpe la acción que se estaba
     * haciendo (aprobar, cancelar, etc.) — esa ya se guardó en la base
     * antes de llegar acá.
     */
    public static function enviar(object $notifiable, Turno $turno, string $evento): void
    {
        try {
            $notifiable->notify(new self($turno, $evento));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Datos que se guardan para mostrar en la campana de notificaciones
     * dentro de la app (no depende de que el mail llegue a destino).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $turno = $this->turno;

        return [
            'evento' => $this->evento,
            'turno_id' => $turno->id,
            'titulo' => $this->asunto(),
            'mensaje' => $this->introduccion(),
            'espacio' => $turno->espacio->nombre,
            'fecha' => $turno->fecha->format('d/m/Y'),
            'horario' => substr($turno->hora_inicio, 0, 5) . ' - ' . substr($turno->hora_fin, 0, 5),
            'observaciones' => $turno->observaciones,
        ];
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
            ->salutation('IES Nuevo Horizonte — Sistema de Turnos')
            ->withSymfonyMessage(function ($symfonyMessage) {
                $logo = public_path('images/logo-ies-mail.png');

                if (!is_file($logo)) {
                    return;
                }

                $parte = (new DataPart(new File($logo), 'logo-ies.png', 'image/png'))->asInline();
                $parte->setContentId(self::CID_LOGO);
                $symfonyMessage->addPart($parte);
            });
    }

    protected function asunto(): string
    {
        return match ($this->evento) {
            self::EVENTO_CREADO => $this->turno->espacio->tipo->requiereAprobacion()
                ? "Turno solicitado — {$this->turno->espacio->nombre}"
                : "Turno reservado — {$this->turno->espacio->nombre}",
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
            self::EVENTO_CREADO => $this->turno->espacio->tipo->requiereAprobacion()
                ? 'Registramos tu solicitud de turno. Queda pendiente de aprobación por administración.'
                : 'Tu reserva quedó confirmada.',
            self::EVENTO_MODIFICADO => 'Se modificó tu turno. Si estaba aprobado, vuelve a quedar pendiente de revisión.',
            self::EVENTO_APROBADO => 'Tu turno fue aprobado.',
            self::EVENTO_RECHAZADO => 'Tu turno fue rechazado.',
            self::EVENTO_CANCELADO => 'Tu turno fue cancelado.',
            default => 'Hubo una actualización en tu turno.',
        };
    }
}
