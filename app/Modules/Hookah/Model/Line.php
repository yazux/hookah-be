<?php

namespace App\Modules\Hookah\Model;

use App\Classes\BaseModel;

class Line extends BaseModel
{
    public $table = 'module_hookah_line';

    public $timestamps = false;

    public $fillable = [
        'id',
        'name',
        'description',
        'vendor_id'
    ];

    public $rules = [
        'name'        => 'required|min:1|max:255',
        'description' => 'required|min:1|max:5000',
        'vendor_id'   => 'required|integer|min:1|max:4294967295'
    ];

    public $messages = [
        'name.required' => "Поле 'Название' обязательно для заполнения",
        'name.min'      => "Минимальная длина значения поля 'Название' - 1 символ",
        'name.max'      => "Максимальная длина значения поля 'Название' - 255 символов",

        'description.required' => "Поле 'Описание' обязательно для заполнения",
        'description.min'      => "Минимальная длина значения поля 'Описание' - 1 символ",
        'description.max'      => "Максимальная длина значения поля 'Описание' - 5000 символов",

        'vendor_id.required' => "Поле 'Поставщик' обязательно для заполнения",
        'vendor_id.min'      => "Поле 'Поставщик' обязательно для заполнения",
        'vendor_id.max'      => "Превышено максимально возможное значение поля 'Поставщик'",
        'vendor_id.integer'  => "Значение поля 'Поставщик' должно содержать только целые числа"
    ];

    public function vendor()
    {
        return $this->belongsTo('App\Modules\Hookah\Model\Vendor', 'vendor_id');
    }
}