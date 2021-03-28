<div class="modal fade close-on-submit" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="form-horizontal" method="PUT" action="{{ route('measures.update', 0) }}">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">
                        {{ _i('Modifica Unità di Misura') }}
                    </h4>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            @include('commons.manyrows', [
                                'contents' => $measures,
                                'show_columns' => true,
                                'columns' => [
                                    [
                                        'label' => _i('ID'),
                                        'field' => 'id',
                                        'type' => 'hidden',
                                        'width' => 0
                                    ],
                                    [
                                        'label' => _i('Nome'),
                                        'field' => 'name',
                                        'type' => 'text',
                                        'width' => 6,
                                        'extra' => [
                                            'mandatory' => true
                                        ]
                                    ],
                                    [
                                        'label' => _i('Unità Discreta'),
                                        'field' => 'discrete',
                                        'help' => _i('Le unità discrete non sono frazionabili: sui prodotti cui viene assegnata una unità di misura etichettata con questo attributo non sarà possibile attivare proprietà come "Prezzo Variabile" e "Pezzatura"'),
                                        'type' => 'bool',
                                        'width' => 2,
                                        'extra' => [
                                            'valuefrom' => 'id',
                                            'extra_class' => 'row-untoggler'
                                        ]
                                    ],
                                    [
                                        'label' => _i('Prodotti'),
                                        'field' => 'id',
                                        'type' => 'custom',
                                        'width' => 2,
                                        'contents' => '<button type="button" class="btn btn-default async-popover" data-contents-url="' . url('measures/list/%s') . '" data-container="body" data-toggle="popover" data-placement="right" data-content="placeholder" data-html="true" data-trigger="hover"><span class="glyphicon glyphicon-th-list" aria-hidden="true"></span></button>'
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ _i('Annulla') }}</button>
                    <button type="submit" class="btn btn-success">{{ _i('Salva') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
