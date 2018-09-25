<?php

namespace App\Modules\Properties\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\CustomValidationException;
use App\Interfaces\ModuleModelInterface;

/**
 * Класс для работы с значениями сущностей
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class PropertiesValues extends Model implements ModuleModelInterface
{

    public $table = 'module_properties_values';
    public $timestamps = true;
    protected static $fields = [
        'id', 'created_at', 'updated_at', 'property_id', 'entity_id', 'code', 'value'
    ];

    /**
     * Вернёт поля таблицы, доступные для выборки
     *
     * @return array
     */
    public static function fields()
    {
        // TODO: Implement fields() method.
        return self::$fields;
    }

    /**
     * Валидатор входных данных для добавления
     * и обновления значений сущностей
     *
     * @param array $data - данные сущности
     *
     * @return bool
     * @throws CustomValidationException
     */
    public function valuesValidator($data)
    {

        $validatorRules = [
            'property_id' => 'required|integer',
            'entity_id' => 'required|integer',
            'code' => 'alpha_dash|max:255|nullable',
            'value' => 'required|max:50000',
        ];
        $data = (array) $data;
        //условие срабатывает, если мы пытаемся обновить запись
        if (array_key_exists('update_id', $data) && $data['update_id']) {
            $validatorRules['code'] = $validatorRules['code'].','.$data['update_id'];
            $validatorRules['id'] = 'required|integer';
        }

        $validator = Validator::make($data, $validatorRules);

        /*if ($validator->fails()) {
            throw new CustomValidationException(
                $validator, 'Data validation error', $data
            );
        } else {
            return true;
        }*/

        return !$validator->fails();
    }

    /**
     * Возвращает свйоство для указанного значения
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function property()
    {
        return $this->belongsTo('App\Modules\Properties\Model\Properties');
    }

    public function values()
    {
        return $this->belongsTo('App\Modules\Properties\Model\PropertiesValues', 'property_id');
    }

    /**
     * Вернёт все варианты выбора для конкретного свойства
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function choices()
    {
        return $this->hasMany('App\Modules\Properties\Model\PropertiesChoices', 'property_id', 'property_id');
    }

    /**
     * Возвращает сущность, с которой связано
     * значеие в переданной модели
     *
     * @param Model $model - объект модели для связи
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entity($model)
    {
        return $this->belongsTo($model);
    }

}