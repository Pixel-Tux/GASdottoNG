<?php

/*
    Questo comando viene usato per aggiornare i database delle istanze in
    produzione per eventuali modifiche allo schema.
    Il suo contenuto cambia nel tempo, man mano che avvengono gli aggiornamenti.
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;

use App\Gas;
use App\User;
use App\Supplier;
use App\ModifierType;
use App\Role;
use App\Date;

class FixDatabase extends Command
{
    protected $signature = 'fix:database';
    protected $description = 'Sistema le informazioni sul DB per completare il deploy';

    public function handle()
    {
        /*
            I seeder dei tipi di movimento contabile e dei tipi di modificatore
            vengono sempre eseguiti, tanto comunque controllano se ogni tipo già
            esiste prima di ricrearlo
        */
        Artisan::call('db:seed', ['--force' => true, '--class' => 'MovementTypesSeeder']);
        Artisan::call('db:seed', ['--force' => true, '--class' => 'ModifierTypesSeeder']);

        /*
            Per revisionare le configurazioni relative ai limiti di credito per
            permettere le prenotazioni
        */
        foreach(Gas::all() as $gas) {
            $restriction_info = $gas->getConfig('restrict_booking_to_credit');
            $restriction_info = json_decode($restriction_info);
            if (is_object($restriction_info) == false) {
                $restriction_info = (object) [
                    'enabled' => $restriction_info,
                    'limit' => 0,
                ];
                $gas->setConfig('restrict_booking_to_credit', $restriction_info);
            }
        }

        /*
            Per assegnare degli identificativi ai modificatori di default,
            compatibili con l'importazione GDXP
        */

        $mod = ModifierType::where('name', 'Sconto')->where('system', true)->where('identifier', '')->first();
        if ($mod) {
            $mod->identifier = 'discount';
            $mod->save();
        }

        $mod = ModifierType::where('name', 'Spese Trasporto')->where('system', true)->where('identifier', '')->first();
        if ($mod) {
            $mod->identifier = 'shipping';
            $mod->save();
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('payment_id')->nullable()->change();
        });

        /*
            Per aggiornare il formato delle date per gli ordini automatici
        */

        $dates = Date::where('type', 'order')->get();
        foreach($dates as $d) {
            $attributes = json_decode($d->description);
            if (isset($attributes->action) == false) {
                $attributes->action = 'open';
                $attributes->offset1 = $attributes->end;
                $attributes->offset2 = $attributes->shipping;
                unset($attributes->end);
                unset($attributes->shipping);
                $d->description = json_encode($attributes);
                $d->save();
            }
        }
    }
}
