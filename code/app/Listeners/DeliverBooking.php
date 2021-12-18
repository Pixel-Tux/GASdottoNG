<?php

namespace App\Listeners;

use App\Events\BookingDelivered;

class DeliverBooking
{
    public function __construct()
    {
        //
    }

    private function testSaved($event)
    {
        $booking = $event->booking;
        $new_status = $event->status;

        return ($new_status == 'saved' && $booking->payment != null);
    }

    private function testShipped($event)
    {
        $booking = $event->booking;
        $user = $event->user;
        $new_status = $event->status;

        /*
            Se sull'istanza locale sto gestendo i pagamenti, quando viene
            salvata una consegna senza pagamento la salvo come "salvata" e
            non "consegnata".
            Questo per evitare che nella fase successiva - appunto, quella
            del pagamento - qualcosa vada storto e la consegna continui a
            risultare consegnata benché senza alcun pagamento.
            La consegna viene effettivamente marcata come consegnata al
            salvataggio del relativo movimento contabile, in MovementType
        */
        return (someoneCan('movements.admin', $user->gas) && $new_status == 'shipped' && $booking->payment == null);
    }

    public function handle(BookingDelivered $event)
    {
        $booking = $event->booking;
        $user = $event->user;
        $new_status = $event->status;

        $booking->deliverer_id = $user->id;
        $booking->delivery = date('Y-m-d');

        if ($this->testSaved($event)) {
            $booking->payment->delete();
            $booking->payment_id = null;
        }
        else if ($this->testShipped($event)) {
            $new_status = 'saved';
        }

        $booking->status = $new_status;
        $booking->save();

        $booking->saveFinalPrices();
        $booking->saveModifiers();

        foreach($booking->friends_bookings as $friend_booking) {
            $friend_booking->status = $new_status;
            $friend_booking->save();
        }

        return $booking;
    }
}
