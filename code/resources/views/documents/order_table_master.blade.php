{{ _i('Utente') }};{{ $has_shippings = ($currentgas->deliveries->isEmpty() == false) ? _i('Luogo di Consegna') . ';' : '' }}<?php foreach ($order->products as $product) {
    $total_price = 0;
    $total_discount = 0;

    if ($product->variants->isEmpty()) {
        $all_products[$product->id] = 0;
        echo sprintf('%s (%s);', $product->printableName(), printablePriceCurrency($product->price, ','));
    }
    else {
        foreach($product->variant_combos as $combo) {
            $all_products[$product->id . '-' . $combo->id] = 0;
            echo sprintf('%s (%s);', $combo->printableName(), printablePriceCurrency($combo->price, ','));
        }
    }
} ?>{{ _i('Totale Prezzo') }};{{ _i('Utente') }};{{ _i('E-Mail') }}

@foreach($selected_bookings as $booking)
{{ $booking->user->printableName() }}{{ $has_shippings ? ';' . ($booking->user->shippingplace != null ? $booking->user->shippingplace->name : '') : ''  }}<?php foreach ($order->products as $product) {
    if ($product->variants->isEmpty()) {
        $quantity = $booking->$get_function($product, $get_function_real, true);
        $all_products[$product->id] += $quantity;
        echo ';' . printableQuantity($quantity, $product->measure->discrete, 3, ',');
    }
    else {
        $quantity = 0;
        foreach($product->variant_combos as $combo) {
            $quantity += $booking->$get_function($combo, $get_function_real, true);
        }

        $all_products[$product->id . '-' . $combo->id] += $quantity;
        echo ';' . printableQuantity($quantity, $product->measure->discrete, 3, ',');
    }
} ?>;<?php $price = $booking->getValue($get_total, $with_friends); $total_price += $price; echo printablePrice($price, ',') ?>;<?php $discount = $booking->getValue('discount', $with_friends); $total_discount += $discount; echo printablePrice($discount, ',') ?>;{{ $booking->user->printableName() }};{{ $booking->user->email }}
@endforeach

TOTALI;{{ $has_shippings ? ';' : '' }}<?php foreach ($order->products as $product) {
    if ($product->variants->isEmpty()) {
        echo printableQuantity($all_products[$product->id], $product->measure->discrete, 3, ',') . ';';
    }
    else {
        foreach($product->variant_combos as $combo) {
            echo printableQuantity($all_products[$product->id . '-' . $combo->id], $product->measure->discrete, 3, ',') . ';';
        }
    }
} ?>{{ printablePrice($total_price, ',') }};{{ printablePrice($total_discount, ',') }};TOTALI

{{ _i('Utente') }};{{ $has_shippings ? _i('Luogo di Consegna') . ';' : '' }}<?php foreach ($order->products as $product) {
    if ($product->variants->isEmpty()) {
        echo $product->printableName() . ';';
    }
    else {
        foreach($product->variant_combos as $combo) {
            echo $combo->printableName() . ';';
        }
    }
} ?>{{ _i('Totale Prezzo') }};{{ _i('Utente') }};{{ _i('E-Mail') }}
