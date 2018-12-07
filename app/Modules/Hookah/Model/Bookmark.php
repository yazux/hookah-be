<?php

namespace App\Modules\Hookah\Model;

use App\Classes\BaseModel;

class Bookmark extends BaseModel
{
    public $table = 'module_hookah_bookmark';

    public $timestamps = true;

    public $fillable = [
        'id',
        'mix_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo('App\Modules\User\Model\User', 'user_id');
    }

    public function mix()
    {
        return $this->belongsTo('App\Modules\Hookah\Model\Mix', 'mix_id');
    }
}