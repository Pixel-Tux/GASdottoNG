<?php

$display_shipping_date = true;

switch($booking->status) {
    case 'shipped':
        $intro_text = _i('Di seguito il riassunto dei prodotti che ti sono stati consegnati:');
        $display_shipping_date = false;
        $attribute = 'delivered';
        $get_value = 'delivered';
        break;

    case 'saved':
        $intro_text = _i('Di seguito il riassunto dei prodotti che ti saranno consegnati:');
        $attribute = 'delivered';
        $get_value = 'delivered';
        break;

    case 'pending':
        $intro_text = _i('Di seguito il riassunto dei prodotti che hai ordinato:');
        $attribute = 'quantity';
        $get_value = 'booked';
        break;
}

$global_total = 0;
$bookings_tot = 0;

?>

@if(!empty($txt_message))
    <p>
        {!! nl2br($txt_message) !!}
    </p>

    <hr/>
@endif

<p>
    {{ $intro_text }}
</p>

@foreach($booking->bookings as $b)
    <?php $variable = false ?>

    <h3>{{ $b->order->supplier->printableName() }}</h3>

    <table style="width:100%">
        <thead>
            <th style="width:50%; text-align: left">{{ _i('Prodotto') }}</th>
            <th style="width:25%; text-align: left">{{ _i('Quantità') }}</th>
            <th style="width:25%; text-align: left">{{ _i('Prezzo') }}</th>
        </thead>

        <tbody>
            @foreach($b->products as $product)
                @if($product->$attribute != 0)
                    <?php $variable = $variable || $product->product->variable ?>
                    <tr>
                        <td>{{ $product->product->printableName() }}</td>
                        <td>{{ $product->$attribute }} {{ $product->product->printableMeasure() }}</td>
                        <td>{{ printablePriceCurrency($product->getValue($get_value)) }}</td>
                    </tr>
                @endif
            @endforeach

            <?php

            $bookings_tot++;
            $tot = $b->getValue('effective', false);
            $global_total += $tot;

            $modifiers = $b->applyModifiers();
            $aggregated_modifiers = App\ModifiedValue::aggregateByType($modifiers);

            ?>

            @foreach($aggregated_modifiers as $am)
                <tr>
                    <td><strong>{{ $am->name }}</strong></td>
                    <td>&nbsp;</td>
                    <td>{{ printablePriceCurrency($am->amount) }}</td>
                </tr>
            @endforeach

            <tr>
                <td><strong>{{ _i('Totale') }}</strong></td>
                <td>&nbsp;</td>
                <td>{{ printablePriceCurrency($tot) }}</td>
            </tr>
        </tbody>
    </table>

    @if($b->friends_bookings->isEmpty() == false)
        <h5>{{ _i('Gli ordini dei tuoi amici') }}</h5>

        @foreach($b->friends_bookings as $fb)
            <p>{{ $fb->user->printableName() }}</p>

            <table style="width:100%">
                <thead>
                    <th style="width:50%; text-align: left">{{ _i('Prodotto') }}</th>
                    <th style="width:25%; text-align: left">{{ _i('Quantità') }}</th>
                    <th style="width:25%; text-align: left">{{ _i('Prezzo') }}</th>
                </thead>

                <tbody>
                    @foreach($fb->products as $product)
                        @if($product->$attribute != 0)
                            <?php $variable = $variable || $product->product->variable ?>
                            <tr>
                                <td>{{ $product->product->printableName() }}</td>
                                <td>{{ $product->$attribute }} {{ $product->product->printableMeasure() }}</td>
                                <td>{{ printablePriceCurrency($product->getValue($get_value)) }}</td>
                            </tr>
                        @endif
                    @endforeach

                    <?php

                    $bookings_tot++;
                    $tot = $fb->getValue('effective', false);
                    $global_total += $tot;

                    $modifiers = $fb->applyModifiers();
                    $aggregated_modifiers = App\ModifiedValue::aggregateByType($modifiers);

                    ?>

                    @foreach($aggregated_modifiers as $am)
                        <tr>
                            <td><strong>{{ $am->name }}</strong></td>
                            <td>&nbsp;</td>
                            <td>{{ printablePriceCurrency($am->amount) }}</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td><strong>{{ _i('Totale') }}</strong></td>
                        <td>&nbsp;</td>
                        <td>{{ printablePriceCurrency($tot) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif

    @if($display_shipping_date && $variable)
        <p>
            {{ _i("L'importo reale di questo ordine dipende dal peso effettivo dei prodotti consegnati; il totale qui riportato è solo indicativo.") }}
        </p>
    @endif
@endforeach

@if($bookings_tot > 1)
    <p>
        {{ _i('Totale da pagare: %s', [printablePriceCurrency($global_total)]) }}
    </p>
@endif

@if($display_shipping_date && $b && $b->order->shipping != null)
    <p>
        {{ _i('La consegna avverrà %s.', [$b->order->printableDate('shipping')]) }}
    </p>
@endif
