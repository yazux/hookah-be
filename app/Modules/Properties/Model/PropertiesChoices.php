<?php

namespace App\Modules\Properties\Model;

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
 * Класс для работы с вариантами выбора свойств сущностей
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class PropertiesChoices extends Model implements ModuleModelInterface
{

    public $table = 'module_properties_choices';
    public $timestamps = false;

    protected static $fields = ['name', 'code', 'sort', 'property_id'];


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
     * и обновления вариантов значений свойств сущностей
     *
     * @param array $data - данные варианта значения
     *
     * @return bool
     * @throws CustomValidationException
     */
    public function choicesValidator($data)
    {

        if (!array_key_exists('sort', $data) || !$data['sort']) {
            $data['sort'] = 100;
        }

        $validatorRules = [
            'name' => 'required|max:255',
            'code' => 'required|alpha_dash|max:255',
            'sort' => 'integer|max:900',
            'property_id' => 'required|integer',
        ];

        //условие срабатывает, если мы пытаемся обновить запись
        if (array_key_exists('update_id', $data) && $data['update_id']) {
            $validatorRules['code'] = $validatorRules['code'].','.$data['update_id'];
            $validatorRules['id'] = 'required|integer';
        }

        $validator = Validator::make($data, $validatorRules);

        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator, 'Data validation error', $data
            );
        } else {
            return true;
        }
    }

    /**
     * Вернёт свойство, к котрому относится вариант выбора
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function property()
    {
        return $this->belongsTo('App\Modules\Properties\Model\Properties');
    }

}