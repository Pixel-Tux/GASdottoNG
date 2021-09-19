<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

use Log;

class Modifier extends Model
{
    use GASModel, Cachable;

    public function modifierType()
    {
        return $this->belongsTo('App\ModifierType');
    }

    public function target()
    {
        return $this->morphTo();
    }

    public function getDefinitionsAttribute()
    {
        $ret = json_decode($this->definition);
        return collect($ret ?: []);
    }

    public function getModelTypeAttribute()
    {
        $ret = strtolower(substr(strrchr($this->target_type, '\\'), 1));
        if ($ret == 'supplier') {
            $ret = 'order';
        }
        return $ret;
    }

    public function isVoid()
    {
        $data = $this->definitions;
        return $data->isEmpty();
    }

    public function isTrasversal()
    {
        if ($this->isVoid()) {
            return false;
        }

        return ($this->value == 'absolute' && $this->applies_target == 'order');
    }

    public function getNameAttribute()
    {
        $data = $this->definitions;

        if ($data->isEmpty()) {
            return _i('Nessun Valore');
        }

        if ($this->value == 'percentage') {
            $postfix = '%';
        }
        else {
            $postfix = currentAbsoluteGas()->currency;
        }

        $ret = [];

        foreach ($data as $d) {
            $ret[] = sprintf('%s%s', $d->amount, $postfix);
        }

        return join(' / ', $ret);
    }

    public function getROShowURL()
    {
        return route('modifiers.show', $this->id);
    }

    public function getActiveAttribute()
    {
        return $this->definitions->isEmpty() == false;
    }

    public static function descriptions()
    {
        /*
            Qui predispongo le stringhe descrittive per tutte le possibili
            combinazioni di valori, destinate a rendere più comprensibile la
            tabella delle soglie.
        */

        $currency = currentAbsoluteGas()->currency;

        $value_labels = [
            'none' => '',
            'quantity' => _i('la quantità'),
            'price' => _i('il valore'),
            'weight' => _i('il peso'),
        ];

        $targets_labels = [
            'product,product' => '',
            'product,booking' => _i('di prodotto nella prenotazione'),
            'product,order' => _i("di prodotto nell'ordine"),
            'order,product' => '',
            'order,booking' => _i('della prenotazione'),
            'order,order' => _i("dell'ordine"),
            'delivery,product' => '',
            'delivery,booking' => _i('della prenotazione destinata al luogo'),
            'delivery,order' => _i("dell'ordine destinato al luogo"),
        ];

        $scale_labels = [
            'minor' => _i('è minore di'),
            'major' => _i('è maggiore di'),
        ];

        $value_units = [
            /*
                La 'X' serve a inizializzare l'input group nell'editor del
                modificatore, di fatto non viene mai visualizzata
            */
            'none' => 'X',
            'quantity' => _i('Prodotti'),
            'price' => $currency,
            'weight' => _i('Chili'),
        ];

        $distribution_labels = [
            'sum,product,absolute' => [_i("somma al costo del prodotto"), $currency],
            'sum,booking,absolute' => [_i("somma al costo della prenotazione"), $currency],
            'sum,order,absolute' => [_i("somma al costo dell'ordine"), $currency],
            'sum,product,percentage' => [_i("somma al costo del prodotto"), '%'],
            'sum,booking,percentage' => [_i("somma al costo della prenotazione"), '%'],
            'sum,order,percentage' => [_i("somma al costo dell'ordine"), '%'],
            'sub,product,absolute' => [_i("sottrai al costo del prodotto"), $currency],
            'sub,booking,absolute' => [_i("sottrai al costo della prenotazione"), $currency],
            'sub,order,absolute' => [_i("sottrai al costo dell'ordine"), $currency],
            'sub,product,percentage' => [_i("sottrai al costo del prodotto"), '%'],
            'sub,booking,percentage' => [_i("sottrai al costo della prenotazione"), '%'],
            'sub,order,percentage' => [_i("sottrai al costo dell'ordine"), '%'],
            'passive,product,absolute' => [_i("rispetto al costo del prodotto, calcola"), $currency],
            'passive,booking,absolute' => [_i("rispetto al costo della prenotazione, calcola"), $currency],
            'passive,order,absolute' => [_i("rispetto al costo dell'ordine, calcola"), $currency],
            'passive,product,percentage' => [_i("rispetto al costo del prodotto, calcola"), '%'],
            'passive,booking,percentage' => [_i("rispetto al costo della prenotazione, calcola"), '%'],
            'passive,order,percentage' => [_i("rispetto al costo dell'ordine, calcola"), '%'],
            'apply,product,price' => [_i("applica il prezzo unitario"), $currency],
            'apply,booking,price' => [_i("applica il prezzo unitario"), $currency],
            'apply,order,price' => [_i("applica il prezzo unitario"), $currency],
        ];

        $distribution_types = [
            'none' => '',
            'quantity' => _i('e distribuiscilo in base alle quantità prenotate'),
            'price' => _i('e distribuiscilo in base al valore delle prenotazioni'),
            'weight' => _i('e distribuiscilo in base al peso delle prenotazioni'),
        ];

        $labels = [];

        foreach($value_labels as $vl => $vs) {
            foreach($targets_labels as $tl => $ts) {
                foreach($scale_labels as $sl => $ss) {
                    foreach($value_units as $vu => $vus) {
                        foreach($distribution_labels as $dl => $ds) {
                            foreach($distribution_types as $dt => $dts) {
                                $key = sprintf('%s,%s,%s,%s,%s,%s', $vl, $tl, $sl, $vu, $dl, $dt);
                                $labels[$key] = [
                                    _i('Se %s %s %s', [$vs, $ts, $ss]),
                                    $vus,
                                    $ds[0],
                                    $ds[1],
                                    $dts,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $labels;
    }

    public function getDescriptionIndexAttribute()
    {
        return sprintf('%s,%s,%s,%s,%s,%s,%s,%s,%s', $this->applies_type, $this->model_type, $this->applies_target, $this->scale, $this->applies_type, $this->arithmetic, $this->applies_target, $this->value, $this->distribution_type);
    }

    private function applyDefinition($amount, $definition, $target)
    {
        $rounding = 4;

        if ($this->value == 'percentage') {
            $amount = round(($amount * $definition->amount) / 100, $rounding);
        }
        else if ($this->value == 'absolute') {
            $amount = $definition->amount;
        }
        else {
            /*
                Per i modificatori che incidono sul prezzo del prodotti
                ($this->value = 'apply') faccio la differenza tra il prezzo
                normale ed il prezzo modificato
            */
            $amount = $target->price - ($target->quantity * $definition->amount);
        }

        return $amount;
    }

    private function retrieveExistingValue($obj_mod_target)
    {
        if ($obj_mod_target) {
            $modifier_value = $obj_mod_target->modifiedValues->firstWhere('modifier_id', $this->id);
            if (is_null($modifier_value)) {
                $modifier_value = new ModifiedValue();
                $modifier_value->setRelation('modifier', $this);
                $obj_mod_target->modifiedValues->push($modifier_value);
            }
        }
        else {
            $modifier_value = new ModifiedValue();
            $modifier_value->setRelation('modifier', $this);
        }

        return $modifier_value;
    }

    public function apply($booking, $aggregate_data)
    {
        if ($this->active == false) {
            return null;
        }

        if (!isset($aggregate_data->orders[$booking->order_id])) {
            Log::debug('Nessun dato su cui applicare il modificatore');
            return null;
        }

        $product_target_id = 0;

        if ($this->target_type == 'App\Product') {
            $product_target_id = $this->target->id;

            switch($this->applies_target) {
                case 'order':
                    $check_target = $aggregate_data->orders[$booking->order_id]->products[$product_target_id] ?? null;
                    break;

                default:
                    $check_target = $aggregate_data->orders[$booking->order_id]->bookings[$booking->id]->products[$product_target_id] ?? null;
                    break;
            }
        }
        else {
            switch($this->applies_target) {
                case 'order':
                    $check_target = $aggregate_data->orders[$booking->order_id] ?? null;
                    break;

                case 'booking':
                    $check_target = $aggregate_data->orders[$booking->order_id]->bookings[$booking->id] ?? null;
                    break;

                case 'product':
                    $check_target = $aggregate_data->orders[$booking->order_id]->bookings[$booking->id]->products[$this->target->id] ?? null;
                    break;

                default:
                    Log::error('applies_target non riconosciuto per modificatore: ' . $this->applies_target);
                    return null;
            }
        }

        switch($this->applies_target) {
            case 'order':
                $mod_target = $aggregate_data->orders[$booking->order_id] ?? null;
                $obj_mod_target = $booking;
                break;

            case 'booking':
                $mod_target = $aggregate_data->orders[$booking->order_id]->bookings[$booking->id] ?? null;
                $obj_mod_target = $booking;
                break;

            case 'product':
                $product_target_id = $this->target->id;
                $mod_target = $aggregate_data->orders[$booking->order_id]->bookings[$booking->id]->products[$product_target_id] ?? null;
                $obj_mod_target = $booking->products()->whereHas('product', function($query) use ($product_target_id) {
                    $query->where('product_id', $product_target_id);
                })->first();
                break;

            default:
                return null;
        }

        $attribute = '';

        if ($booking->status == 'shipped' || $booking->status == 'saved') {
            switch($this->applies_type) {
                case 'none':
                case 'quantity':
                    $attribute = 'delivered';
                    break;
                case 'price':
                    $attribute = 'price_delivered';
                    break;
                case 'weight':
                    $attribute = 'weight_delivered';
                    break;
            }

            $mod_attribute = 'price_delivered';
        }
        else {
            $attribute = $this->applies_type;
            if ($attribute == 'none') {
                $attribute = 'price';
            }

            $mod_attribute = 'price';
        }

        $check_value = $check_target->$attribute ?? 0;
        $target_definition = null;
        $altered_amount = null;

        if ($check_value == 0) {
            $altered_amount = 0;
        }
        else {
            if ($this->scale == 'minor') {
                foreach($this->definitions as $def) {
                    if ($check_value < $def->threshold) {
                        $target_definition = $def;
                        break;
                    }
                }
            }
            else if ($this->scale == 'major') {
                foreach($this->definitions as $def) {
                    if ($check_value > $def->threshold) {
                        $target_definition = $def;
                        break;
                    }
                }
            }

            if (is_null($target_definition) == false) {
                $altered_amount = $this->applyDefinition($mod_target->$mod_attribute ?? 0, $target_definition, $check_target);

                /*
                    Se il modificatore è applicato su un ordine, qui applico alla
                    singola prenotazione il suo valore relativo e proporzionale.
                */
                if ($this->applies_target == 'order') {
                    $distribution_attribute = $this->distribution_type;
                    if ($distribution_attribute == 'none') {
                        $distribution_attribute = 'price';
                    }

                    $distribution_attribute = 'relative_' . $distribution_attribute;

                    if ($this->target_type == 'App\Product') {
                        $booking_mod_target = $aggregate_data->orders[$booking->order_id]->bookings[$booking->id]->products[$product_target_id] ?? null;
                        $reference = $mod_target->products[$product_target_id]->$distribution_attribute;
                    }
                    else {
                        $booking_mod_target = $aggregate_data->orders[$booking->order_id]->bookings[$booking->id] ?? null;
                        $reference = $mod_target->$distribution_attribute;
                    }

                    if ($booking_mod_target && $reference) {
                        $altered_amount = (($booking_mod_target->$distribution_attribute * $altered_amount) / $reference);
                    }
                    else {
                        $altered_amount = 0;
                    }
                }
            }
            else {
                Log::error('Unable to apply any threshold for modifier ' . $this->id);

                $modifier_value = $this->retrieveExistingValue($obj_mod_target);
                if ($modifier_value->exists) {
                    $modifier_value->delete();
                }

                return null;
            }
        }

        $modifier_value = $this->retrieveExistingValue($obj_mod_target);

        /*
            Se alla fine il modificatore non modifica nulla, lo ignoro (e ne
            elimino il valore esistente, se c'è)
        */
        if ($altered_amount == 0) {
            if ($modifier_value->exists) {
                $modifier_value->delete();
            }

            return null;
        }

        $modifier_value->modifier_id = $this->id;
        $modifier_value->amount = $altered_amount;

        if ($obj_mod_target) {
            $modifier_value->target_type = get_class($obj_mod_target);
            $modifier_value->target_id = $obj_mod_target->id;
            $modifier_value->save();
        }

        return $modifier_value;
    }
}
