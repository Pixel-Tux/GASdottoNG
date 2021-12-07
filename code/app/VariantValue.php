<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

use App\Events\SluggableCreating;

class VariantValue extends Model
{
    use GASModel, SluggableID, Cachable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $dispatchesEvents = [
        'creating' => SluggableCreating::class,
    ];

    public function variant()
    {
        return $this->belongsTo('App\Variant');
    }

    public function getSlugID()
    {
        return sprintf('%s::%s', $this->variant_id, Str::slug($this->value));
    }
}
