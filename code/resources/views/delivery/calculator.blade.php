<?php

$rand = rand();
$modal_id = sprintf('calculator-modal-%s', $rand);

?>

<div class="input-group-text inline-calculator-trigger" data-bs-toggle="modal" data-bs-target="#{{ $modal_id }}">
    <i class="bi-plus-lg"></i>
</div>

@push('postponed')
    <x-larastrap::modal :title="_i('Calcola Quantità')" classes="inline-calculator" :id="$modal_id" size="md">
        <div class="alert alert-info mb-2">
            Indica qui il peso dei singoli pezzi coinvolti nella consegna per ottenere la somma.
        </div>

        <x-larastrap::form>
            @for($i = 0; $i < $pieces; $i++)
                <div class="form-group mb-2">
                    <div class="input-group">
                        <input type="text" class="form-control number" autocomplete="off" value="0">
                        <div class="input-group-text">{{ $measure }}</div>
                    </div>
                </div>
            @endfor
        </x-larastrap::form>
    </x-larastrap::modal>
@endpush
