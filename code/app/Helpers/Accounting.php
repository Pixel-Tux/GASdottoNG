<?php

/*
    Qui vengono letti i tipi di movimento contabile dal database, e le strutture
    dati di quelli "di sistema" (che non possono essere eliminati) vengono
    eventualmente arricchite con le callback definite dalle classi parametriche
*/
function movementTypes($identifier = null, $with_trashed = false)
{
    static $types = null;

    if ($identifier == 'VOID') {
        $types = null;
        return null;
    }

    if (is_null($types)) {
        $query = App\MovementType::orderBy('name', 'asc');
        if ($with_trashed) {
            $query = $query->withTrashed();
        }

        $from_database = $query->get();
        $predefined = systemParameters('MovementType');
        $types = new Illuminate\Support\Collection();

        foreach($from_database as $mov) {
            $mov->callbacks = [];

            if (isset($predefined[$mov->id])) {
                $mov = $predefined[$mov->id]->systemInit($mov);
            }

            $types->push($mov);
        }
    }

    if ($identifier) {
        $ret = $types->where('id', $identifier)->first();
        if (is_null($ret)) {
            /*
                Questo è per compatibilità coi controlli usati in giro, che
                assumono venga usato findOrFail() sulle query eseguite sul
                database
            */
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Error Processing Request", 1);
        }
    }
    else {
        $ret = $types;
    }

    return $ret;
}

function paymentTypes()
{
    $ret = [];

    $predefined = systemParameters('PaymentType');
    foreach($predefined as $identifier => $obj) {
        if ($obj->enabled()) {
            $ret[$identifier] = $obj->definition();
        }
    }

    return $ret;
}

function paymentsSimple()
{
    $payments = paymentTypes();

    $ret = [
        'none' => _i('Non Specificato'),
    ];

    foreach($payments as $identifier => $meta) {
        $ret[$identifier] = $meta->name;
    }

    return $ret;
}

function paymentMethodByType($type)
{
    $movement_methods = paymentTypes();
    return $movement_methods[$type] ?? null;
}

function paymentsByType($type)
{
    $function = null;

    if ($type != null) {
        $metadata = movementTypes($type);
        if ($metadata) {
            $function = json_decode($metadata->function);
        }
    }

    $movement_methods = paymentTypes();
    $ret = [];

    foreach ($movement_methods as $method_id => $info) {
        if ($function) {
            foreach($function as $f) {
                if ($f->method == $method_id) {
                    $ret[$method_id] = $info->name;
                    break;
                }
            }
        }
    }

    return $ret;
}

function defaultPaymentByType($type)
{
    $metadata = movementTypes($type);
    $function = json_decode($metadata->function);

    foreach($function as $f) {
        if (isset($f->is_default) && $f->is_default) {
            return $f->method;
        }
    }

    if (empty($function)) {
        return null;
    }
    else {
        return $function[0]->method;
    }
}
