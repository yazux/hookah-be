<?php

namespace App\Modules\Hookah\Model;

use App\Classes\BaseModel;

class MixTobacco extends BaseModel
{
    public $table = 'module_hookah_mix_tobacco';

    public $timestamps = false;

    public $fillable = [
        'id',
        'mix_id',
        'vendor_id',
        'percent',
        'flavor'
    ];

    public function vendor()
    {
        return $this->belongsTo('App\Modules\Hookah\Model\Vendor', 'vendor_id');
    }

    public function mix()
    {
        return $this->belongsTo('App\Modules\Hookah\Model\Mix', 'mix_id');
    }
}