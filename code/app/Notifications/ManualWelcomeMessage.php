<?php

namespace App\Notifications;

use App\Notifications\ManyMailNotification;
use App\Notifications\MailFormatter;

class ManualWelcomeMessage extends ManyMailNotification
{
    use MailFormatter;

    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function toMail($notifiable)
    {
        $message = $this->initMailMessage($notifiable);

        return $this->formatMail($message, 'manual_welcome', [
            'username' => $notifiable->username,
            'gas_access_link' => route('autologin', ['token' => $this->token]),
            'gas_login_link' => route('login'),
        ]);
    }
}
