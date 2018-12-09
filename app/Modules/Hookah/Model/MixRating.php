<?php

namespace App\Modules\Hookah\Model;

use App\Classes\BaseModel;

class MixRating extends BaseModel
{
    public $table = 'module_hookah_mix_rating';

    public $timestamps = false;

    public $fillable = [
        'id',
        'mix_id',  //id микса
        'user_id', //id пользователя
        'rating'   //оценка 1 - положительная, 0 - отрицательная
    ];

    public $rules = [
        'mix_id'  => 'required|integer|min:1|max:4294967295',
        'user_id' => 'required|integer|min:1|max:4294967295',
        'rating'  => 'required|numeric|min:0|max:4294967295',
    ];

    public $messages = [
        'mix_id.required' => "Поле 'Микс' обязательно для заполнения",
        'mix_id.integer'  => "В поле 'Микс' требуется ввести число",
        'mix_id.min'      => "Минимальное значене поля 'Микс' - 1",
        'mix_id.max'      => "Максимальное значение поля 'Микс' - 4294967295",

        'user_id.required' => "Поле 'Пользователь' обязательно для заполнения",
        'user_id.integer'  => "В поле 'Пользователь' требуется ввести число",
        'user_id.min'      => "Минимальное значене поля 'Пользователь' - 1",
        'user_id.max'      => "Максимальное значение поля 'Пользователь' - 4294967295",

        'rating.required' => "Поле 'Рейтинг' обязательно для заполнения",
        'rating.numeric'  => "В поле 'Рейтинг' требуется ввести число",
        'rating.min'      => "Минимальное значене поля 'Рейтинг' - 0",
        'rating.max'      => "Максимальное значение поля 'Рейтинг' - 4294967295",
    ];

    /**
     * Связь с пользователем
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Modules\User\Model\User', 'user_id');
    }

    /**
     * Связь с миксом
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mix()
    {
        return $this->belongsTo('App\Modules\Hookah\Model\Mix', 'mix_id');
    }

}