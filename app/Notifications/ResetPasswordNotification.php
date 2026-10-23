<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expiracao = config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire'
        );

        return (new MailMessage)
            ->subject('Redefina sua senha - Squad Maker')
            ->greeting('Olá, '.$notifiable->nickname.'!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta no Squad Maker.')
            ->action('Redefinir senha', $url)
            ->line('Por segurança, este link expira em '.$expiracao.' minutos.')
            ->line('Se você não solicitou a alteração, ignore este e-mail. Sua senha continuará a mesma.')
            ->salutation('Equipe Squad Maker');
    }
}
