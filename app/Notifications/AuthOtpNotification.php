<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuthOtpNotification extends Notification
{
    use Queueable;

    public function __construct(public string $code, public string $purpose) {}

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
        return (new MailMessage)
            ->subject($this->purpose === 'login' ? 'Your CityCare sign-in code' : 'Your CityCare password reset code')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->purpose === 'login'
                ? 'Use this one-time code to finish signing in to your CityCare account.'
                : 'Use this one-time code to reset your CityCare account password.')
            ->line('OTP Code: ' . $this->code)
            ->line('This code expires in 10 minutes and can only be used once. If you did not request it, you can ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
