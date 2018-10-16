<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">{{ _i('Stato Crediti') }}</h4>
</div>

<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group hidden-md">
                <div class="input-group">
                    <div class="input-group-addon">
                        <label class="radio-inline">
                            <input type="radio" name="filter_mode" value="min" checked> {{ _i('Minore di') }}
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="filter_mode" value="max"> {{ _i('Maggiore di') }}
                        </label>
                    </div>
                    <input type="number" class="form-control table-number-filter" placeholder="{{ _i('Filtra Credito') }}" data-list-target="#creditsTable">
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" id="credits_status_table">
            <table class="table" id="creditsTable">
                <thead>
                    <tr>
                        @if($currentgas->hasFeature('rid'))
                            <th width="50%">{{ _i('Nome') }}</th>
                            <th width="35%">{{ _i('Credito Residuo') }}</th>
                            <th width="15%">{{ _i('IBAN') }}</th>
                        @else
                            <th width="60%">{{ _i('Nome') }}</th>
                            <th width="40%">{{ _i('Credito Residuo') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($currentgas->users as $user)
                        <tr>
                            <td>
                                <input type="hidden" name="user_id[]" value="{{ $user->id }}">
                                {{ $user->printableName() }}
                            </td>

                            <td class="text-filterable-cell">
                                {{ printablePriceCurrency($user->current_balance_amount) }}
                            </td>

                            @if(!empty($currentgas->rid['iban']))
                                <td>
                                    @if(empty($user->rid['iban']))
                                        <span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>
                                    @else
                                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-footer">
    <a href="{{ url('movements/document/credits/csv?credit=all') }}" class="btn btn-success">{{ _i('Esporta CSV') }}</a>
    <a type="button" class="btn btn-success" data-toggle="collapse" href="#exportRID">{{ _i('Esporta SEPA') }}<span class="caret"></span></a>
    <a type="button" class="btn btn-success" data-toggle="collapse" href="#sendCreditsMail">{{ _i('Notifica Utente Visualizzati') }}<span class="caret"></span></a>

    <div class="collapse well" id="exportRID">
        <form class="form-horizontal form-filler" method="GET" action="">
            @include('commons.datefield', [
                'obj' => null,
                'name' => 'date',
                'label' => _i('Data'),
                'mandatory' => true,
                'defaults_now' => true
            ])

            @include('commons.textfield', [
                'obj' => null,
                'name' => 'body',
                'label' => _i('Causale'),
                'default_value' => _i('VERSAMENTO GAS')
            ])

            <a href="{{ url('movements/document/credits/rid?download=1') }}" class="btn btn-success form-filler-download">{{ _i('Esporta SEPA') }}</a>
        </form>
    </div>

    <div class="collapse well" id="sendCreditsMail">
        <form class="form-horizontal inner-form" method="POST" action="{{ route('notifications.store') }}">
            <input type="hidden" name="close-modal" value="1">
            <input type="hidden" name="pre-saved-function" value="collectFilteredUsers">
            @include('notification.base-edit', ['notification' => null, 'select_users' => false])
            <button type="submit" class="btn btn-success">{{ _i('Notifica') }}</button>
        </form>
    </div>
</div>
