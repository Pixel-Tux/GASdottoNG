<?php

list($value, $is_percentage) = readPercentage($obj ? $obj->$name : '');

if (!isset($help_text)) {
    $help_text = '';
}

?>

<div class="form-group">
    @if($squeeze == false)
        <label for="{{ $prefix . $name . $postfix }}" class="col-sm-{{ $labelsize }} control-label">{{ $label }}</label>
    @endif

    <div class="col-sm-{{ $fieldsize }}">
        <div class="input-group">
            <input type="text"
                class="form-control number trim-2-ddigits"
                name="{{ $prefix . $name . $postfix }}"
                value="{{ $value }}"

                @if($mandatory ?? false)
                    required
                @endif

                @if($squeeze == true)
                    placeholder="{{ $label }}"
                @endif

                autocomplete="off">

            <div class="input-group-addon">
                <label class="radio-inline">
                    <input type="radio" name="{{ $name }}_percentage_type" value="euro" {{ $is_percentage == false ? 'checked' : '' }}> {{ $currentgas->currency }}
                </label>
                <label class="radio-inline">
                    <input type="radio" name="{{ $name }}_percentage_type" value="percentage" {{ $is_percentage ? 'checked' : '' }}> %
                </label>
            </div>
        </div>

        @if(!empty($help_text))
            <span class="help-block">{!! $help_text !!}</span>
        @endif
    </div>
</div>
