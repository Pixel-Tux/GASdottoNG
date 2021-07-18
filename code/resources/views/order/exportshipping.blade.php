<x-larastrap::modal :title="_i('Dettaglio Consegne')" classes="close-on-submit order-document-download-modal">
    <x-larastrap::form classes="direct-submit" method="GET" :action="url('orders/document/' . $order->id . '/shipping')" label_width="2" input_width="10">
        <p>
            {{ _i("Da qui puoi ottenere un documento in cui si trovano le informazioni relative alle singole prenotazioni. Utile da consultare mentre si effettuano le consegne.") }}
        </p>
        <p>
            {!! _i("Per la consultazione e l'elaborazione dei files in formato CSV (<i>Comma-Separated Values</i>) si consiglia l'uso di <a target=\"_blank\" href=\"http://it.libreoffice.org/\">LibreOffice</a>.") !!}
        </p>

        <hr/>

        @if($currentgas->hasFeature('shipping_places'))
            <?php

            $options = [
                'all_by_name' => _i('Tutti (ordinati per utente)'),
                'all_by_place' => _i('Tutti (ordinati per luogo)'),
            ];

            foreach($currentgas->deliveries as $delivery) {
                $options[$delivery->id] = $delivery->name;
            }

            ?>
            <x-larastrap::radios name="shipping_place" :label="_i('Luogo di Consegna')" :options="$options" value="all_by_name" />
        @endif

        <?php list($options, $values) = flaxComplexOptions(App\User::formattableColumns()) ?>
        <x-larastrap::checks name="fields" :label="_i('Dati Utenti')" :options="$options" :value="$values" />

        <?php list($options, $values) = flaxComplexOptions(App\Order::formattableColumns('shipping')) ?>
        <x-larastrap::checks name="fields" :label="_i('Colonne Prodotti')" :options="$options" :value="$values" />

        <x-larastrap::radios name="status" :label="_i('Stato Prenotazioni')" :options="['booked' => _i('Prenotate'), 'delivered' => _i('Consegnate')]" value="booked" />

        <x-larastrap::radios name="format" :label="_i('Formato')" :options="['pdf' => _i('PDF'), 'csv' => _i('CSV')]" value="pdf" />

        @include('order.filesmail', ['contacts' => $order->supplier->involvedEmails()])
    </x-larastrap::form>
</x-larastrap::modal>
