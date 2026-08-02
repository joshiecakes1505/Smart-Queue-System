<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SendTwoFactorCode extends Notification
{
    use Queueable;

    public function __construct(
        protected string $context = 'login',
    ) {
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
        $subject = $this->context === 'password reset'
            ? 'Your Password Reset Verification Code'
            : 'Your Two-Factor Authentication Security Code';

        $instruction = $this->context === 'password reset'
            ? 'If you did not request a password reset, you can ignore this message.'
            : 'If you did not initiate this login request, please change your security settings immediately.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Maligayang araw po, ' . $notifiable->name . '!')
            ->line(new HtmlString('Your two-factor authentication security code is: <strong style="font-size: 32px; font-weight: bold;">' . $notifiable->two_factor_code . '</strong>'))
            ->line('This code will expire in 10 minutes.')
            ->line($instruction);
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
