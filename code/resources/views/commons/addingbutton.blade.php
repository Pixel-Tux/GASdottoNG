<?php

if (isset($target_update) == false) {
    $target_update = $typename.'-list';
}
if (isset($button_label) == false) {
    $button_label = _i('Crea Nuovo %s', $typename_readable);
}

if (isset($extra_size) == false || $extra_size == false) {
    $size_class = 'modal-lg';
}
else {
    $size_class = 'modal-extra-lg';
}

?>

<button type="button" class="btn btn-warning pull-right" data-toggle="modal" data-target="#create{{ ucfirst($typename) }}">{{ $button_label }}</button>

@if(isset($dynamic_url))
    <div class="modal fade dynamic-contents close-on-submit" id="create{{ ucfirst($typename) }}" tabindex="-1" role="dialog" data-contents-url="{{ $dynamic_url }}">
        <div class="modal-dialog {{ $size_class }}" role="document">
            <div class="modal-content">
            </div>
        </div>
    </div>
@else
    <div class="modal fade" id="create{{ ucfirst($typename) }}" tabindex="-1" role="dialog">
        <div class="modal-dialog {{ $size_class }}" role="document">
            <div class="modal-content">
                <form class="form-horizontal creating-form" method="POST" action="/{{ $targeturl }}" data-toggle="validator">
                    <input type="hidden" name="update-list" value="{{ $target_update }}">
                    @include('commons.extrafields')

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">{{ $button_label }}</h4>
                    </div>
                    <div class="modal-body">
                        @include($template, [$typename => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ _i('Annulla') }}</button>
                        <button type="submit" class="btn btn-success">{{ _i('Salva') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
