<?php

if(!isset($default_value)) {
    $default_value = '';
}

$value = accessAttr($obj, $name, '');
if(empty($value)) {
    $value = $default_value;
}

$class = 'form-control';
if (isset($extra_class)) {
    $class .= ' ' . $extra_class;
}

if (!isset($max_length)) {
    /*
        Assumo che la lunghezza massima per le stringe semplici sia la lunghezza
        massima dei campi "string" sul database
    */
    $max_length = Illuminate\Database\Schema\Builder::$defaultStringLength - 1;
}

$wrap_class = 'form-group';
if (isset($extra_wrap_class)) {
    $wrap_class .= ' ' . $extra_wrap_class;
}

if (!isset($help_text)) {
    $help_text = '';
}

?>

<div class="{{ $wrap_class }}">
    @if($squeeze == false)
        <label for="{{ $prefix . $name . $postfix }}" class="col-sm-{{ $labelsize }} control-label">{{ $label }}</label>
    @endif

    <div class="col-sm-{{ $fieldsize }}">
        @if(isset($postlabel))
            <div class="input-group">
        @endif

        <input type="text"
            class="{{ $class }}"
            name="{{ $prefix . $name . $postfix }}"
            value="{{ $value }}"
            maxlength="{{ $max_length }}"

            @if(isset($mandatory) && $mandatory == true)
                required
            @endif

            @if(isset($disabled) && $disabled == true)
                disabled
            @endif

            @if($squeeze == true)
                placeholder="{{ $label }}"
            @endif

            @if(isset($enforced_default))
                data-default-value="{{ $enforced_default }}"
            @endif

            autocomplete="off">

        @if(isset($postlabel))
            <div class="input-group-addon">{{ $postlabel }}</div>
            </div>
        @endif

        @if(!empty($help_text))
            <span class="help-block">{{ $help_text }}</span>
        @endif
    </div>
</div>
