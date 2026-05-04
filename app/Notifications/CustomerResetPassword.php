<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPassword extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public Store $store,
    ) {}

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
        $resetUrl = route('customer.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject(__('Reset your :store password', ['store' => $this->store->name]))
            ->line(__('You are receiving this email because we received a password reset request for your account.'))
            ->action(__('Reset password'), $resetUrl)
            ->line(__('This password reset link will expire in :count minutes.', [
                'count' => config('auth.passwords.customers.expire', 60),
            ]))
            ->line(__('If you did not request a password reset, no further action is required.'));
    }
}
