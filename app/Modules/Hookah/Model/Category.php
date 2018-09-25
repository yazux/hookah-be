<?php

namespace App\Modules\Hookah\Model;

use App\Classes\BaseModel;

class Category extends BaseModel
{
    public $table = 'module_hookah_category';

    public $timestamps = false;

    public $fillable = [
        'id',
        'name',
        'description'
    ];

    public $rules = [
        'name'          => 'required|min:1|max:255',
        'description'   => 'nullable|min:1|max:5000'
    ];

    public $messages = [
        'name.required' => "Поле 'Название' обязательно для заполнения",
        'name.min'      => "Минимальная длина значения поля 'Название' - 1 символ",
        'name.max'      => "Максимальная длина значения поля 'Название' - 255 символов",

        'description.required' => "Поле 'Описание' обязательно для заполнения",
        'description.min'      => "Минимальная длина значения поля 'Описание' - 1 символ",
        'description.max'      => "Максимальная длина значения поля 'Описание' - 5000 символов"
    ];
}