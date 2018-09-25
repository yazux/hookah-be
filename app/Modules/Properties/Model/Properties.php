<?php

namespace App\Modules\Properties\Model;

use App\Exceptions\CustomException;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\CustomValidationException;
use App\Exceptions\CustomDBException;
use League\Flysystem\Exception;
use App\Interfaces\ModuleModelInterface;
use App\Modules\Module\Model\Module;

/**
 * Класс для работы с свойствами сущностей
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class Properties extends Model implements ModuleModelInterface
{

    public $table = 'module_properties';

    protected static $fields = [
        'name', 'code', 'sort', 'module_id', 'default', 'module_code',
        'field_entity', 'field_type', 'multiply', 'require', 'active', 'size', 'category_id', 'show_in_filter'
    ];

    /**
     * Все возможные типы полей, которые могут принимать свойства
     *
     * @var array
     */
    private $propertyType = [
        'text',
        'textarea',
        'checkbox',
        'number',
        'radio',
        'select',
        'tel',
        'email',
        'range',
        'file',
        'hidden',
        'image',
        'password',
        'color',
        'date',
        'datetime',
        'time',
        'url',
        'category_id',
        'show_in_filter'
    ];

    private static $propertySelectinsType = ['checkbox', 'radio', 'select'];

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
     * и обновления свойств сущностей
     *
     * @param $data
     *
     * @return bool
     *
     * @throws CustomException
     * @throws CustomValidationException
     */
    public function propertiesValidator($data)
    {

        if (!array_key_exists('sort', $data) || !$data['sort']) {
            $data['sort'] = 100;
        }

        if (!array_key_exists('default', $data)) {
            $data['default'] = 0;
        }

        if (!array_key_exists('size', $data)) {
            $data['size'] = 100;
        }

        //проверим, является ли тип поля допустимым
        if (!in_array($data['field_type'], $this->propertyType)) {
            throw new CustomException(
                ['data' => $data, 'property' => $data],
                false, 400, "'".$data['field_type']." не является корректным типом"
            );
        }

        $validatorRules = [
            'name'         => 'required|min:0|max:255',
            'code'         => 'required|min:1|max:255|alpha_dash',
            'sort'         => 'integer|min:0|max:900',
            'module_id'    => 'required|integer|max:999',
            'field_entity' => 'required|alpha_dash|max:255',
            'field_type'   => 'required|alpha_dash|max:255',
            'multiply'     => 'required|boolean|min:0|max:1', // 0/1
            'require'      => 'required|boolean|min:0|max:1', // 0/1
            'active'       => 'required|boolean|min:0|max:1', // 0/1
            'default'      => 'required|boolean|min:0|max:1|nullable', // 0/1,
            'category_id'  => 'nullable|integer|min:0|max:4294967295',
            'show_in_filter' => 'nullable|integer|min:0|max:4294967295'
        ];

        //условие срабатывает, если мы пытаемся обновить запись
        if (array_key_exists('update_id', $data) && $data['update_id']) {
            $validatorRules['code'] = $validatorRules['code'].','.$data['update_id'];
            $validatorRules['id'] = 'required|integer';
        }

        $validator = Validator::make($data, $validatorRules);

        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator,
                'Ошибка значения свойства',
                $data
            );
        } else {
            return true;
        }
    }

    /**
     * Валидирует тип нового значения свойства модуля
     *
     * @param string $propertyValue - значение свойства
     * @param string $propertyType  - тип свойства
     *
     * @return bool
     * @throws CustomValidationException
     */
    public function propertyValueTypeValidator($propertyValue, $propertyType)
    {
        $propertyValueArray = ['value' => $propertyValue];
        $ruleArray = [];

        switch ($propertyType) {
        case 'text':
            $ruleArray = ['value' => 'required|max:255'];
            break;
        case 'textarea':
            $ruleArray = ['value' => 'required|max:2500'];
            break;
        case 'checkbox':
            $ruleArray = ['value' => 'required|max:255'];
            break;
        case 'number':
            $ruleArray = ['value' => 'required|numeric'];
            break;
        case 'radio':
            $ruleArray = ['value' => 'required|max:255'];
            break;
        case 'select':
            $ruleArray = ['value' => 'required|max:255'];
            break;
        case 'tel':
            $ruleArray = ['value' => 'required|max:255'];
            break;
        case 'email':
            $ruleArray = ['value' => 'required|email'];
            break;
        case 'range':
            $ruleArray = ['value' => 'required|numeric'];
            break;
        case 'file':
            $ruleArray = ['value' => 'required'];
            break;
        case 'hidden':
            $ruleArray = ['value' => 'required|max:2500'];
            break;
        case 'image':
            $ruleArray = ['value' => 'required|image'];
            break;
        case 'password':
            $ruleArray = ['value' => 'required|max:255'];
            break;
        case 'color':
            $ruleArray = ['value' => 'required|max:255'];
            break;
        case 'date':
            $ruleArray = ['value' => 'required|date_format:d.m.Y'];
            break;
        case 'datetime':
            $ruleArray = ['value' => 'required|date_format:d.m.Y G:i:s'];
            break;
        case 'time':
            $ruleArray = ['value' => 'required|date_format:G:i:s'];
            break;
        case 'url':
            $ruleArray = ['value' => 'required|url|max:2500'];
            break;
        }

        $validator = Validator::make((array)$propertyValueArray, $ruleArray);

        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator,
                'Ошибка в значении свойства',
                [
                    'prop_value' => $propertyValue,
                    'prop_type' => $propertyType
                ]
            );
        } else {
            return true;
        }

    }

    /**
     * Проверяет, является ли свойство с переданным ID множественным
     *
     * @param int $id - ID свойства, которое нужно проверить
     *
     * @return bool
     */
    public static function isMultiply($id)
    {
        $property = self::where('id', $id)->first();

        return ($property && $property['multyply']) ? true : false;
    }

    /**
     * Проверяет, является ли свойство с переданным ID вариантивным
     * (имеет возможность выбора из нескольких вариантов)
     *
     * @param int $id - ID свойства, которое нужно проверить
     *
     * @return bool
     */
    public static function isSelecting($id)
    {
        $property = self::where('id', $id)->first();

        if (!$property) return false;

        return (in_array($property['field_type'], self::$propertySelectinsType)) ? true : false;
    }

    /**
     * Вернёт все варианты выбора для конкретного свойства
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function choices()
    {
        return $this->hasMany('App\Modules\Properties\Model\PropertiesChoices', 'property_id', 'id');
    }

    /**
     * Вернёт модуль, к котрому отновится свойство
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function module()
    {
        return $this->belongsTo('App\Modules\Module\Model\Module');
    }

    /**
     * Вернёт значения конкретного свойства
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function values()
    {
        return $this->hasMany('App\Modules\Properties\Model\PropertiesValues', 'property_id', 'id');
    }


    public function category()
    {
        return $this->belongsTo('App\Modules\Market\Model\Category', 'category_id');
    }
}