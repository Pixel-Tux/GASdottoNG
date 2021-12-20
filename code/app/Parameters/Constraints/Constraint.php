<?php

/*
    Ciascuna delle classi che estendono questa rappresenta un controllo
    effettuato su un prodotto in fase di prenotazione. Se viene riscontrato un
    errore, viene sollevata una eccezione che viene poi gestita da
    DynamicBookingsService.
    I controlli possono essere di due tipi: se la quantità valutata non è
    coerente e dunque invalida viene sollevata una eccezione di tipo
    InvalidQuantityConstraint, altrimenti se si vuole solo notificare l'utente
    di qualcosa (senza invalidare la quantità) viene sollevata una eccezione di
    tipo AnnotatedQuantityConstraint
*/

namespace App\Parameters\Constraints;

use App\Parameters\Parameter;

abstract class Constraint extends Parameter
{
    public function hardContraint()
    {
        return true;
    }

    /*
        I Constraints possono essere hard o soft. Nel secondo caso sono dei
        semplici suggerimenti, che comportano la visualizzazione di un messaggio
        ma non l'interruzione dell'operazione (e.g. quantità prenotate oltre il
        massimo consigliato).
        Pertanto qui li ordino affinché possano essere valutati prima quelli
        hard e poi, se non sono state sollevate eccezioni bloccanti, quelli soft
    */
    public static function sortedContraints()
    {
        $constraints = systemParameters('Constraints');

        $sorted_contraints = [
            0 => [],
            1 => [],
        ];

        foreach($constraints as $constraint) {
            if ($constraint->hardContraint()) {
                $sorted_contraints[0][] = $constraint;
            }
            else {
                $sorted_contraints[1][] = $constraint;
            }
        }

        return $sorted_contraints;
    }

    public abstract function printable($product, $order);
    public abstract function test($booked, $quantity);
}
