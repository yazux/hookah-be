<?php

namespace App\Modules\Hookah\Model;

use App\Classes\BaseModel;

class Vendor extends BaseModel
{
    public $table = 'module_hookah_vendor';

    public $timestamps = false;

    public $fillable = [
        'id',
        'name',
        'country',
        'description',
        'hero_image_id',
        'rating'
    ];

    public $rules = [
        'name'          => 'required|min:1|max:255',
        'description'   => 'required|min:1|max:5000',
        'country'       => 'required|min:1|max:255',
        'hero_image_id' => 'required|integer|min:0|max:4294967295',
        'rating'        => 'required|numeric|min:0|max:4294967295',
    ];

    public $messages = [
        'name.required' => "Поле 'Название' обязательно для заполнения",
        'name.min'      => "Минимальная длина значения поля 'Название' - 1 символ",
        'name.max'      => "Максимальная длина значения поля 'Название' - 255 символов",

        'description.required' => "Поле 'Описание' обязательно для заполнения",
        'description.min'      => "Минимальная длина значения поля 'Описание' - 1 символ",
        'description.max'      => "Максимальная длина значения поля 'Описание' - 5000 символов",

        'country.required' => "Поле 'Страна' обязательно для заполнения",
        'country.min'      => "Минимальная длина значения поля 'Страна' - 1 символ",
        'country.max'      => "Максимальная длина значения поля 'Страна' - 255 символов",

        'hero_image_id.required' => "Поле 'Изображение' обязательно для заполнения",
        'hero_image_id.min'      => "Поле 'Изображение' обязательно для заполнения",
        'hero_image_id.max'      => "Превышено максимально возможное значение поля 'Изображение'",
        'hero_image_id.integer'  => "Значение поля 'Изображение' должно содержать только целые числа"
    ];

    public function heroImage()
    {
        return $this->belongsTo('App\Modules\Properties\Model\Files', 'hero_image_id');
    }
}