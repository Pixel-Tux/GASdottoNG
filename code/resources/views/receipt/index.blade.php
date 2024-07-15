@php

$loadable_attributes = [
    'identifier' => 'receipts-list',
    'items' => $receipts,
];

if ($user_id == '0') {
    $actions = [
        ['link' => route('receipts.search', ['format' => 'send']), 'label' => _i('Inoltra Ricevute in Attesa')],
    ];

    $downloads = [
        ['link' => route('receipts.search', ['format' => 'csv']), 'label' => _i('Esporta CSV')],
    ];

    $loadable_attributes['legend'] = (object)['class' => App\Receipt::class];
}
else {
    $actions = [];
    $downloads = [];
}

@endphp

<div>
    <div class="row">
        <div class="col-12 col-md-6">
            <x-filler :data-action="route('receipts.search')" data-fill-target="#receipts-in-range" :actionButtons="$actions" :downloadButtons="$downloads">
                @include('commons.genericdaterange', ['start_date' => strtotime('-1 months')])
                <x-larastrap::hidden name="user_id" :value="$user_id" />
                <x-larastrap::selectobj name="supplier_id" :label="_i('Fornitore')" :options="$currentgas->suppliers" :extraitem="_i('Nessuno')" />
            </x-filler>
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col" id="receipts-in-range">
            @include('commons.loadablelist', $loadable_attributes)
        </div>
    </div>
</div>
