<?php

if ($target) {
    if (!is_null($target->deleted_at)) {
        $status = 'deleted';
    }
    else if (!is_null($target->suspended_at)) {
        $status = 'suspended';
    }
    else {
        $status = 'active';
    }

    if (is_a($target, 'App\User')) {
        $help_popover = _i('Gli utenti Sospesi e Cessati non possono accedere alla piattaforma, pur restando registrati. È necessario specificare una data di cessazione/sospensione.');
    }
}
else {
    $status = 'active';
}

?>

@if($squeeze == false)
    <div class="form-group status-selector">
        <label class="col-sm-{{ $labelsize }} control-label">
            @include('commons.helpbutton', ['help_popover' => $help_popover])
            {{ _i('Stato') }}
        </label>

        <div class="col-sm-{{ $fieldsize }}">
            <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-default {{ $status == 'active' ? 'active' : '' }}">
                    <input type="radio" name="{{ $prefix . 'status' . $postfix }}" value="active" {{ $status == 'active' ? 'checked' : '' }}> {{ _i('Attivo') }}
                </label>
                <label class="btn btn-default {{ $status == 'suspended' ? 'active' : '' }}">
                    <input type="radio" name="{{ $prefix . 'status' . $postfix }}" value="suspended" {{ $status == 'suspended' ? 'checked' : '' }}> {{ _i('Sospeso') }}
                </label>
                <label class="btn btn-default {{ $status == 'deleted' ? 'active' : '' }}">
                    <input type="radio" name="{{ $prefix . 'status' . $postfix }}" value="deleted" {{ $status == 'deleted' ? 'checked' : '' }}> {{ _i('Cessato') }}
                </label>
            </div>
        </div>
        <div class="status-date-deleted col-sm-offset-{{ $labelsize }} col-sm-{{ $fieldsize }} {{ $status != 'deleted' ? 'hidden' : '' }}">
            @include('commons.datefield', ['obj' => $target, 'name' => 'deleted_at', 'label' => _i('Data'), 'squeeze' => true, 'prefix' => $prefix, 'postfix' => $postfix])
        </div>
        <div class="status-date-suspended col-sm-offset-{{ $labelsize }} col-sm-{{ $fieldsize }} {{ $status != 'suspended' ? 'hidden' : '' }}">
            @include('commons.datefield', ['obj' => $target, 'name' => 'suspended_at', 'label' => _i('Data'), 'squeeze' => true, 'prefix' => $prefix, 'postfix' => $postfix])
        </div>
    </div>
@else
    <div class="form-group status-selector">
        <div class="col-sm-{{ $fieldsize }}">
            <div class="row">
                <div class="col-md-5">
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default {{ $status == 'active' ? 'active' : '' }}">
                            <input type="radio" name="{{ $prefix . 'status' . $postfix }}" value="active" {{ $status == 'active' ? 'checked' : '' }}> <span class="glyphicon glyphicon-thumbs-up" aria-hidden="true"></span>
                        </label>
                        <label class="btn btn-default {{ $status == 'suspended' ? 'active' : '' }}">
                            <input type="radio" name="{{ $prefix . 'status' . $postfix }}" value="suspended" {{ $status == 'suspended' ? 'checked' : '' }}> <span class="glyphicon glyphicon-thumbs-down" aria-hidden="true"></span>
                        </label>
                        <label class="btn btn-default {{ $status == 'deleted' ? 'active' : '' }}">
                            <input type="radio" name="{{ $prefix . 'status' . $postfix }}" value="deleted" {{ $status == 'deleted' ? 'checked' : '' }}> <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
                        </label>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="status-date-deleted {{ $status != 'deleted' ? 'hidden' : '' }}">
                        @include('commons.datefield', ['obj' => $target, 'name' => 'deleted_at', 'label' => _i('Data'), 'squeeze' => true, 'prefix' => $prefix, 'postfix' => $postfix])
                    </div>
                    <div class="status-date-suspended {{ $status != 'suspended' ? 'hidden' : '' }}">
                        @include('commons.datefield', ['obj' => $target, 'name' => 'suspended_at', 'label' => _i('Data'), 'squeeze' => true, 'prefix' => $prefix, 'postfix' => $postfix])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
