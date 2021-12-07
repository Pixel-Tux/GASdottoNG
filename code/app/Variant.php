<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

use App\Events\SluggableCreating;

class Variant extends Model
{
    use GASModel, SluggableID, Cachable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $dispatchesEvents = [
        'creating' => SluggableCreating::class,
    ];

    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    public function values()
    {
        return $this->hasMany('App\VariantValue')->orderBy('value', 'asc');
    }

    public function printableValues()
    {
        return $this->values->pluck('value')->join(', ');
    }

    public function getSlugID()
    {
        return sprintf('%s::%s', $this->product_id, Str::slug($this->name));
    }
}
