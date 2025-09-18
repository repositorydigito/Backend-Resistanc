<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $id = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());
        $url = url("/email/verify/{$id}/{$hash}");

        return (new MailMessage)
            ->subject('Verificar dirección de correo electrónico')
            ->greeting('¡Hola!')
            ->line('Por favor, haga clic en el botón de abajo para verificar su dirección de correo electrónico.')
            ->action('Confirme su correo electrónico', $url)
            ->line('Si no ha creado una cuenta, no se requiere ninguna acción adicional.')
            ->salutation('Saludos,')
            ->salutation('resistanc');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
