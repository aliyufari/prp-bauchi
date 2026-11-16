<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $app = config('app.name', 'PRP Bauchi State');

        return (new MailMessage)
            ->subject("Reset your {$app} password")
            ->greeting('Hello '.trim($notifiable->first_name ?? '').',')
            ->line('We received a request to reset the password of your account.')
            ->action('Reset password', $this->resetUrl($notifiable))
            ->line("This link will expire in {$minutes} minutes.")
            ->line('If you did not request a password reset, you can safely ignore this e-mail. Your password will not change.')
            ->salutation("Regards,\n{$app}");
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
