<?php

namespace App\Models\Concerns;

use Log;

trait BookerTrait
{
    /*
        Reminder: non cadere nella tentazione di non utilizzare esplicitamente
        questi trait in User, altrimenti non funzionano alcuni controlli (e.g.
        la visualizzazione del credito utente nel modale di pagamento consegna)
    */
    use CreditableTrait, FriendTrait;

    public function bookings()
    {
        return $this->hasMany('App\Booking')->orderBy('created_at', 'desc');
    }

    public function getLastBookingAttribute()
    {
        $last = $this->bookings()->first();
        if ($last == null) {
            return null;
        }
        else {
            return $last->created_at;
        }
    }

    public function canBook()
    {
        if ($this->gas->restrict_booking_to_credit) {
            if ($this->isFriend()) {
                return $this->parent->canBook();
            }
            else {
                return $this->activeBalance() > 0;
            }
        }
        else {
            return true;
        }
    }

    /*
        Questa funzione ritorna la cifra dovuta dall'utente per le prenotazioni
        fatte dall'utente e non ancora pagate, ma senza considerare anche gli
        eventuali amici
    */
    public function getPendingBalanceAttribute()
    {
        $bookings = $this->bookings()->where('status', 'pending')->whereHas('order', function($query) {
            $query->whereIn('status', ['open', 'closed']);
        })->get();

        $value = 0;

        foreach($bookings as $b) {
            $value += $b->getValue('effective', false);
        }

        return $value;
    }

    /*
        Attenzione: questa funzione ritorna solo il saldo in euro
    */
    public function activeBalance()
    {
        if ($this->isFriend()) {
            return $this->parent->activeBalance();
        }
        else {
            $current_balance = $this->currentBalanceAmount();
            $to_pay = $this->pending_balance;

            foreach($this->friends as $friend) {
                $tpf = $friend->pending_balance;
                $to_pay += $tpf;
            }

            return $current_balance - $to_pay;
        }
    }
}
