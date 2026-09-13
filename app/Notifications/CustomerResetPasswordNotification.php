<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $email,
        public string $storeHandle,
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
        $url = route('customer.password.reset', ['token' => $this->token]).'?'.http_build_query([
            'email' => $this->email,
            'store' => $this->storeHandle,
        ]);

        return (new MailMessage)
            ->subject('Reset your password')
            ->line('You requested a password reset for your store account.')
            ->action('Reset Password', $url)
            ->line('This link expires in 60 minutes.')
            ->line('If you did not request this, no action is needed.');
    }
}
