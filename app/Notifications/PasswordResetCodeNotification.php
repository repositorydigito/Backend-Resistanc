<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The password reset code.
     *
     * @var string
     */
    public $code;

    /**
     * Create a new notification instance.
     *
     * @param  string  $code
     * @return void
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Código de Recuperación de Contraseña - ' . config('app.name'))
            ->greeting('¡Hola ' . $this->getUserName() . '!')
            ->line('Has solicitado restablecer tu contraseña.')
            ->line('Tu código de verificación es:')
            ->line('**' . $this->code . '**')
            ->line('Este código expirará en 10 minutos.')
            ->line('Si no solicitaste este código, puedes ignorar este mensaje.')
            ->salutation('Saludos, ' . config('app.name'))
            ->markdown('emails.password-reset-code', [
                'code' => $this->code,
                'userName' => $this->getUserName(),
                'expireTime' => 10,
            ]);
    }

    /**
     * Get the user's name for the email.
     *
     * @return string
     */
    protected function getUserName()
    {
        return $this->notifiable->name ?? 'Usuario';
    }
}
