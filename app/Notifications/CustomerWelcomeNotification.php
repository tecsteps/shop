<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class CustomerWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly int $storeId) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $token = $this->createToken($notifiable);

        $url = url('/account/set-password?token='.$token.'&email='.urlencode((string) $notifiable->email));

        return (new MailMessage)
            ->subject('Welcome to your account')
            ->line('Thanks for your order. We have created an account for you.')
            ->action('Set your password', $url)
            ->line('If you did not expect this email you can ignore it.');
    }

    protected function createToken(object $notifiable): string
    {
        if (! $notifiable instanceof Customer) {
            return '';
        }

        return Password::broker('customers')->createToken($notifiable);
    }
}
