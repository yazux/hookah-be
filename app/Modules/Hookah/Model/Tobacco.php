<?php

namespace App\Modules\Hookah\Model;

use App\Classes\BaseModel;

class Tobacco extends BaseModel
{
    public $table = 'module_hookah_tobacco';

    public $timestamps = false;

    public $fillable = [
        'id',            //идентификатор
        'name',          //название
        'description',   //описание
        'vendor_id',     //производитель
        'line_id',       //линейка табака
        'hero_image_id', //изображение табака
        'fortress',      //крепость
        'composition',   //cостав
        'variety',       //сорт табака
        'nicotine'       //никотин в %
    ];

    public $rules = [
        'name'          => 'required|min:1|max:255',
        'description'   => 'nullable|min:1|max:5000',
        'vendor_id'     => 'required|integer|min:1|max:4294967295',
        'line_id'       => 'required|integer|min:1|max:4294967295',
        'hero_image_id' => 'nullable|integer|min:1|max:4294967295',
        'fortress'      => 'required|min:1|max:255',
        'composition'   => 'required|min:1|max:255',
        'variety'       => 'required|min:1|max:255',
        'nicotine'      => 'required|numeric|min:0|max:4294967295'
    ];

    public $messages = [
        'name.required' => "Поле 'Название' обязательно для заполнения",
        'name.min'      => "Минимальная длина значения поля 'Название' - 1 символ",
        'name.max'      => "Максимальная длина значения поля 'Название' - 255 символов",

        'description.required' => "Поле 'Описание' обязательно для заполнения",
        'description.min'      => "Минимальная длина значения поля 'Описание' - 1 символ",
        'description.max'      => "Максимальная длина значения поля 'Описание' - 5000 символов",

        'line_id.required' => "Поле 'Линейка' обязательно для заполнения",
        'line_id.min'      => "Поле 'Линейка' обязательно для заполнения",
        'line_id.max'      => "Превышено максимально возможное значение поля 'Линейка'",
        'line_id.integer'  => "Значение поля 'Линейка' должно содержать только целые числа",

        'vendor_id.required' => "Поле 'Поставщик' обязательно для заполнения",
        'vendor_id.min'      => "Поле 'Поставщик' обязательно для заполнения",
        'vendor_id.max'      => "Превышено максимально возможное значение поля 'Поставщик'",
        'vendor_id.integer'  => "Значение поля 'Поставщик' должно содержать только целые числа",

        'hero_image_id.required' => "Поле 'Изображение' обязательно для заполнения",
        'hero_image_id.min'      => "Поле 'Изображение' обязательно для заполнения",
        'hero_image_id.max'      => "Превышено максимально возможное значение поля 'Изображение'",
        'hero_image_id.integer'  => "Значение поля 'Изображение' должно содержать только целые числа",

        'fortress.required' => "Поле 'Крепость' обязательно для заполнения",
        'fortress.min'      => "Минимальная длина значения поля 'Крепость' - 1 символ",
        'fortress.max'      => "Максимальная длина значения поля 'Крепость' - 255 символов",

        'composition.required' => "Поле 'Состав' обязательно для заполнения",
        'composition.min'      => "Минимальная длина значения поля 'Состав' - 1 символ",
        'composition.max'      => "Максимальная длина значения поля 'Состав' - 255 символов",

        'variety.required' => "Поле 'Сорт табака' обязательно для заполнения",
        'variety.min'      => "Минимальная длина значения поля 'Сорт табака' - 1 символ",
        'variety.max'      => "Максимальная длина значения поля 'Сорт табака' - 255 символов",

        'nicotine.required' => "Поле 'Содержание никотина' обязательно для заполнения",
        'nicotine.min'      => "Поле 'Содержание никотина' обязательно для заполнения",
        'nicotine.max'      => "Превышено максимально возможное значение поля 'Содержание никотина'",
        'nicotine.numeric'  => "Значение поля 'Содержание никотина' должно содержать только числа",
    ];

    public function vendor()
    {
        return $this->belongsTo('App\Modules\Hookah\Model\Vendor', 'vendor_id');
    }

    public function line()
    {
        return $this->belongsTo('App\Modules\Hookah\Model\Line', 'Line_id');
    }

    public function heroImage()
    {
        return $this->belongsTo('App\Modules\Properties\Model\Files', 'hero_image_id');
    }
}