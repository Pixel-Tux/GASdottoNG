<?php

/*
    Problema sulla formattazione del documento: c'è chi lo preferisce senza
    interruzioni di pagina in mezzo alle tabelle, e chi lo vuole continuo (per
    evitare tanti spazi vuoti nelle pagine stampate).
    Qui scelgo in base alla dimensione delle prenotazioni: se ce ne sono di
    grandi (più di 15 prodotti) opto per la visualizzazione continua, altrimenti
    preferisco quella che predilige l'accorpamento delle tabelle nelle pagine
*/
$preferred_style = 'breakup';
foreach($data->contents as $d){
    if (count($d->products) >= 15) {
        $preferred_style = 'compact';
        break;
    }
}

?>

<html>
    <head>
        <style>
            table {
                border-spacing: 0;
                border-collapse: collapse;
            }
        </style>
    </head>

    <body>
        @if(isset($order))
            <h3>{{ _i('Dettaglio Consegne Ordine %s a %s del %s', [$order->internal_number, $order->supplier->printableName(), $order->shipping ? date('d/m/Y', strtotime($order->shipping)) : date('d/m/Y')]) }}</h3>
        @else
            <h3>
                {{ _i('Dettaglio Consegne del %s', [$aggregate->shipping ? date('d/m/Y', strtotime($aggregate->shipping)) : date('d/m/Y')]) }}<br/>
                @if($aggregate->orders()->count() <= App\Aggregate::aggregatesConvenienceLimit())
                    @foreach($aggregate->orders as $order)
                        {{ $order->supplier->name }} {{ $order->internal_number }}<br/>
                    @endforeach
                @endif
            </h3>
        @endif

        <br/>
        <hr>
        <br/>

        <table border="1" style="width: 100%" cellpadding="5" nobr="true">
            <tr>
                <th colspan="{{ count($fields->product_columns) }}">
                    {!! join('<br>', $fields->user_columns_names) !!}
                </th>
            </tr>

            <tr>
                @foreach($fields->product_columns_names as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </table>

        <br/>
        <hr>
        <br/>

        <?php $total = $total_transport = $total_discount = 0 ?>

        @foreach($data->contents as $d)
            @if($preferred_style == 'breakup')
                <table style="width: 100%">
                    <tr>
                        <td>
            @endif

            <table border="1" style="width: 100%" cellpadding="5" nobr="true">
                <tr>
                    <th colspan="{{ count($fields->product_columns) }}">
                        {!! join('<br>', array_filter($d->user)) !!}

                        <?php

                        $booking_total = $d->totals['total'];
                        $discount = $d->totals['discount'];
                        $transport = $d->totals['transport'];
                        $total += $booking_total;
                        $total_transport += $transport;
                        $total_discount += $discount;

                        ?>
                    </th>
                </tr>

                @foreach($d->products as $product)
                    <tr>
                        @foreach($product as $p)
                            <td>{{ $p }}</td>
                        @endforeach
                    </tr>
                @endforeach

                @if(!empty($d->notes))
                    <tr>
                        <td colspan="{{ count($fields->product_columns) }}">{!! join('<br>', $d->notes) !!}</td>
                    </tr>
                @endif

                @if($transport != 0)
                    <tr>
                        <th colspan="{{ count($fields->product_columns) }}"><strong>{{ _i('Trasporto') }}: {{ printablePriceCurrency($transport, ',') }}</strong></th>
                    </tr>
                @endif

                @if($discount != 0)
                    <tr>
                        <th colspan="{{ count($fields->product_columns) }}"><strong>{{ _i('Sconto') }}: {{ printablePriceCurrency($discount, ',') }}</strong></th>
                    </tr>
                @endif

                <tr>
                    <th colspan="{{ count($fields->product_columns) }}"><strong>{{ _i('Totale') }}: {{ printablePriceCurrency($booking_total, ',') }}</strong></th>
                </tr>
            </table>

            @if($preferred_style == 'breakup')
                        </td>
                    </tr>
                </table>
            @endif

            <p>&nbsp;</p>
        @endforeach

        <table border="1" style="width: 100%" cellpadding="5" nobr="true">
            @if(!empty($total_transport))
                <tr>
                    <th colspan="3"><strong>{{ _i('Trasporto') }}: {{ printablePriceCurrency($total_transport, ',') }}</strong></th>
                </tr>
            @endif

            @if(!empty($total_discount))
                <tr>
                    <th colspan="3"><strong>{{ _i('Sconto') }}: {{ printablePriceCurrency($total_discount, ',') }}</strong></th>
                </tr>
            @endif

            <tr>
                <th colspan="3"><strong>{{ _i('Totale') }}: {{ printablePriceCurrency($total, ',') }}</strong></th>
            </tr>
        </table>
    </body>
</html>
