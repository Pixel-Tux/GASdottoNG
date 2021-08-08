<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/*
    Di norma le notifiche mail vanno a leggere il campo "email" dell'oggetto da
    notificare, ma nel nostro caso i contatti sono da un'altra parte e possono
    essere molteplici.
    Le classi per le notifiche che estendono questa qua vanno a popolare i
    destinatari delle mail tenendo conto di questo.
*/
class ManyMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        $this->connection = config('queue.default');
        return ['mail'];
    }

    protected function initMailMessage($notifiable, $replyTo = null)
    {
        $message = new MailMessage();

        if (in_array('App\ContactableTrait', class_uses(get_class($notifiable)))) {
            $notifiable->messageAll($message);
        }

        if (!empty($replyTo)) {
            if (is_string($replyTo)) {
                $message->replyTo($replyTo);
            }
            else {
                if (!empty($replyTo->email))
                    $message->replyTo($replyTo->email);
            }
        }

        return $message;
    }
}
