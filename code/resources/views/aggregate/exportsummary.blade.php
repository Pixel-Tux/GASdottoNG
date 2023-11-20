<?php

$hub = App::make('GlobalScopeHub');

if ($hub->enabled() == false) {
    $active_gas = null;
    $managed_gas = 0;
}
else {
    $active_gas = $hub->getGasObj();
    $managed_gas = $active_gas->id;
}

?>

<x-larastrap::modal :title="_i('Riassunto Prodotti')" classes="order-document-download-modal">
    <x-larastrap::form classes="direct-submit" method="GET" :action="route('aggregates.document', ['id' => $aggregate->id, 'type' => 'summary'])">
        <p>
            {{ ("Da qui puoi ottenere un documento che riassume le quantità prenotate di tutti i prodotti: utile da inviare al fornitore, una volta chiuso l'ordine.") }}
        </p>
        <p>
            {!! _i("Per la consultazione e l'elaborazione dei files in formato CSV (<i>Comma-Separated Values</i>) si consiglia l'uso di <a target=\"_blank\" href=\"http://it.libreoffice.org/\">LibreOffice</a>.") !!}
        </p>

        <hr/>

        <input type="hidden" name="managed_gas" value="{{ $managed_gas }}">

        @include('commons.selectshippingexport', ['aggregate' => $aggregate, 'included_metaplace' => ['no', 'all_by_place']])

        <?php list($options, $values) = flaxComplexOptions(App\Formatters\Order::formattableColumns('summary')) ?>
        <x-larastrap::checks name="fields" :label="_i('Colonne')" :options="$options" :value="$values" />

        <x-larastrap::radios name="status" :label="_i('Stato Prenotazioni')" :options="['pending' => _i('Prenotate'), 'shipped' => _i('Consegnate')]" value="pending" />
        <x-larastrap::radios name="format" :label="_i('Formato')" :options="['pdf' => _i('PDF'), 'csv' => _i('CSV'), 'gdxp' => _i('GDXP')]" value="pdf" />
    </x-larastrap::form>
</x-larastrap::modal>
