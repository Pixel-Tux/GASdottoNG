<?php

namespace App\Notifications;

use Auth;

use App\Notifications\ManyMailNotification;

class GenericNotificationWrapper extends ManyMailNotification
{
    private $notification = null;

    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    public function toMail($notifiable)
    {
        $user = Auth::user();
        $message = $this->initMailMessage($notifiable, $user);
        $message->subject(_i('Nuova notifica da %s', [$user->gas->name]))->view('emails.notification', ['notification' => $this->notification]);

        foreach($this->notification->attachments as $attachment) {
            $message->attach($attachment->path, ['as' => $attachment->name]);
        }

        return $message;
    }
}
