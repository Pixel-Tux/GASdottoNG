<div class="form-group">
    @if($squeeze == false)
        <label for="contacts" class="col-sm-{{ $labelsize }} control-label">
            @include('commons.helpbutton', ['help_popover' => _i("Qui si può specificare un numero arbitrario di contatti per il soggetto. Le notifiche saranno spedite a tutti gli indirizzi e-mail indicati.")])
            {{ _i('Contatti') }}
        </label>
    @endif

    <div class="col-sm-{{ $fieldsize }}">
        @include('commons.manyrows', [
            'contents' => $obj ? $obj->contacts : [],
            'extra_class' => 'contacts-selection',
            'columns' => [
                [
                    'label' => _i('ID'),
                    'field' => 'id',
                    'type' => 'hidden',
                    'width' => 0,
                    'extra' => [
                        'prefix' => 'contact_'
                    ]
                ],
                [
                    'label' => _i('Tipo'),
                    'field' => 'type',
                    'type' => 'selectenum',
                    'width' => 4,
                    'extra' => [
                        'prefix' => 'contact_',
                        'values' => App\Contact::types()
                    ]
                ],
                [
                    'label' => _i('Valore'),
                    'field' => 'value',
                    'type' => 'text',
                    'width' => 7,
                    'extra' => [
                        'prefix' => 'contact_'
                    ]
                ]
            ]
        ])
    </div>
</div>
