<?php

/*
    Questo middleware permette di interagire dinamicamente con lo scope globale
    del GAS di riferimento.
    Se non viene passato nessun parametro "managed_gas", agisce solo sul GAS
    corrente (quello dell'utente attualmente autenticato).
    Se "managed_gas" = 0, viene disabilitato il filtro sul GAS e si accedono a
    tutti i dati di tutti i GAS dell'istanza.
    Altrimenti, si accedono ai dati del GAS di cui viene specificato l'ID.

    Cfr. GlobalScopeHub e RestrictedGAS
*/

namespace App\Http\Middleware;

use App;
use Auth;
use Closure;

class ActIntoGas
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if ($user) {
            $managed_gas = $request->input('managed_gas');
            $hub = App::make('GlobalScopeHub');

            if ($managed_gas == null) {
                $managed_gas = $user->gas->id;
                $hub->setGas($managed_gas);
            }
            else if ($managed_gas == 0) {
                $hub->enable(false);
            }
            else {
                $hub->setGas($managed_gas);
            }
        }

        return $next($request);
    }
}
