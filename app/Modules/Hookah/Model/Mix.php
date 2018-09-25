<?php

namespace App\Modules\Hookah\Model;

use App\Classes\BaseModel;

class Mix extends BaseModel
{
    public $table = 'module_hookah_mix';

    public $timestamps = false;

    public $fillable = [
        'id',
        'name',         //название микса
        'description',  //описание
        'stowage',      //укладка табака
        'coal',         //количество углей
        'liquid',       //наполнение колбы
        'additionally', //дополнительная информация,
        'rating'
    ];

    public $rules = [
        'name'         => 'required|min:1|max:255',
        'description'  => 'nullable|min:1|max:5000',
        'stowage'      => 'required|min:1|max:5000',
        'coal'         => 'required|min:1|max:255',
        'liquid'       => 'required|min:1|max:255',
        'additionally' => 'nullable|min:1|max:5000',
        'rating'       => 'required|numeric|min:1|max:4294967295',
    ];

    public $messages = [
        'name.required' => "Поле 'Название' обязательно для заполнения",
        'name.min'      => "Минимальная длина значения поля 'Название' - 1 символ",
        'name.max'      => "Максимальная длина значения поля 'Название' - 255 символов",

        'description.required' => "Поле 'Описание' обязательно для заполнения",
        'description.min'      => "Минимальная длина значения поля 'Описание' - 1 символ",
        'description.max'      => "Максимальная длина значения поля 'Описание' - 5000 символов",

        'stowage.required' => "Поле 'Укладка табака' обязательно для заполнения",
        'stowage.min'      => "Минимальная длина значения поля 'Укладка табака' - 1 символ",
        'stowage.max'      => "Максимальная длина значения поля 'Укладка табака' - 5000 символов",

        'coal.required' => "Поле 'Угли' обязательно для заполнения",
        'coal.min'      => "Минимальная длина значения поля 'Угли' - 1 символ",
        'coal.max'      => "Максимальная длина значения поля 'Угли' - 255 символов",

        'liquid.required' => "Поле 'Наполнение колбы' обязательно для заполнения",
        'liquid.min'      => "Минимальная длина значения поля 'Наполнение колбы' - 1 символ",
        'liquid.max'      => "Максимальная длина значения поля 'Наполнение колбы' - 255 символов",

        'additionally.required' => "Поле 'Дополнительно' обязательно для заполнения",
        'additionally.min'      => "Минимальная длина значения поля 'Дополнительно' - 1 символ",
        'additionally.max'      => "Максимальная длина значения поля 'Дополнительно' - 255 символов"
    ];

    /**
     * Получение свойства "Укладка табака"
     *
     * @param $value - строка с json
     *
     * @return array
     */
    public function getStowageAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Установка свойства "Укладка табака"
     *
     * @param $value - json объект
     */
    public function setStowageAttribute($value)
    {
        $this->attributes['stowage'] = json_encode($value);
    }

    /**
     * Связь с категориями
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function category()
    {
        return $this->belongsToMany(
            'App\Modules\Hookah\Model\Category',
            'module_hookah_mix_category', 'mix_id', 'category_id'
        );
    }

    /**
     * Связь с табаком
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
    public function tobacco()
    {
        return $this->belongsToMany(
            'App\Modules\Hookah\Model\Vendor',
            'module_hookah_mix_tobacco', 'mix_id', 'vendor_id'
        );
    }
    */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tobacco()
    {
        return $this->hasMany('App\Modules\Hookah\Model\MixTobacco', 'mix_id');
    }


    /**
     * Обновление связи микса с вендорами табака
     *
     * @param $tobacco
     *
     * @return array|bool
     */
    public function syncTobacco($tobacco)
    {
        if (!$this || !$this->id) return false;
        $result = [];
        foreach ($tobacco as $item) {
            $item['mix_id'] = $this->id;

            $MixTobacco = MixTobacco::aFind($item);

            if ($MixTobacco) {
                $MixTobacco->update($item);
            } else {
                $MixTobacco = MixTobacco::create($item);
            }
            $result[] = $MixTobacco;
        }

        return $result;
    }

}