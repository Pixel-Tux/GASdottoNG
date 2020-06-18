<?php

$domid = Illuminate\Support\Str::random(10);

?>

<br/>

<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <div class="form-horizontal form-filler" data-action="{{ route('movements.index') }}" data-toggle="validator" data-fill-target="#movements-in-range-{{ $domid }}">
                    @include('commons.genericdaterange')
                    <input type="hidden" name="generic_target_id" value="{{ $target->id }}">
                    <input type="hidden" name="generic_target_type" value="{{ get_class($target) }}">

                    <div class="form-group">
                        <div class="col-md-{{ $fieldsize }} col-md-offset-{{ $labelsize }}">
                            <button type="submit" class="btn btn-info">{{ _i('Ricerca') }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-md-offset-3 current-balance">
                @include('movement.status', ['obj' => $target])
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-12" id="movements-in-range-{{ $domid }}">
                <?php

                $startdate = date('Y-m-d', strtotime('-1 months'));
                $enddate = date('Y-m-d');
                $movements = $target->queryMovements(null)->where('registration_date', '>=', $startdate)->where('registration_date', '<=', $enddate)->get()

                ?>

                @include('movement.bilist', ['movements' => $movements, 'main_target' => $target])
            </div>
        </div>
    </div>
</div>
