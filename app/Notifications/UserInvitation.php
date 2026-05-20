<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $invitationUrl,
        private readonly bool $microsoftOnly = false,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your mammo care HK CRM invitation')
            ->greeting('Hello '.$notifiable->name.',');

        if ($this->microsoftOnly) {
            return $message
                ->line('An administrator created a mammo care HK CRM account for you.')
                ->line('Your account is configured to sign in with Microsoft Entra.')
                ->action('Open mammo care HK CRM', $this->invitationUrl)
                ->line('Use your approved Microsoft account email to continue.');
        }

        return $message
            ->line('An administrator created a mammo care HK CRM account for you.')
            ->line('Use the secure invitation link below to set your password and sign in.')
            ->action('Set password and sign in', $this->invitationUrl)
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
