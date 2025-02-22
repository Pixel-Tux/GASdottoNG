<?php $repository = App::make('RemoteRepository') ?>

<x-larastrap::modal :title="_i('Indice Remoto')">
    <div class="wizard_page">
        <div class="row">
            <div class="col-12">
                <p>
                    {{ _i("Questa funzione permette di accedere e tenere automaticamente aggiornati i listini condivisi su %s. Attenzione: è una funzione sperimentale, usare con cautela!", [env('HUB_URL')]) }}
                </p>
                <hr>
            </div>
            <div class="col-12">
                <input type="text" class="form-control table-text-filter" data-table-target="#remoteSuppliers">
                <hr>
            </div>
            <div class="col-12">
                <table class="table" id="remoteSuppliers">
                    <thead>
                        <tr>
                            <th scope="col" width="25%">{{ _i('Nome') }}</th>
                            <th scope="col" width="20%">{{ _i('Partita IVA') }}</th>
                            <th scope="col" width="25%">{{ _i('Aggiornato') }}</th>
                            <th scope="col" width="25%">{{ _i('Ultima Lettura') }}</th>
                            <th scope="col" width="5%">{{ _i('Importa') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <?php $mine = App\Supplier::where('vat', $entry->vat)->first() ?>
                            <tr>
                                <td><span class="text-filterable-cell">{{ $entry->name }} ({{ $entry->locality }})</span></td>
                                <td><span class="text-filterable-cell">{{ $entry->vat }}</span></td>
                                <td>{{ printableDate($entry->lastchange) }}</td>
                                <td>{{ $mine ? printableDate($mine->remote_lastimport) : _i('Mai') }}</td>
                                <td>
                                    <form action="{{ url('import/gdxp') }}" method="POST">
                                        <input type="hidden" name="step" value="read">
                                        <input type="hidden" name="url" value="{{ $repository->getSupplierLink($entry->vat) }}">
                                        <button type="submit" class="btn btn-sm btn-success">{{ $mine ? _i('Aggiorna') : _i('Importa') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-larastrap::modal>
