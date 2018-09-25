<?php

namespace App\Modules\User\Model;

use App\Exceptions\Handler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\CustomValidationException;
use App\Exceptions\CustomDBException;
use League\Flysystem\Exception;
use App\Interfaces\ModuleModelInterface;


/**
 * Класс для работы с действиями пользователей в модулях
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class Action extends Model implements ModuleModelInterface
{
    public $table = 'module_users_group_actions';

    /**
     * Поля таблицы, доступные для выборки
     *
     * @var array
     */
    protected static $fields = [
        'id', 'name', 'group_id', 'module_id', 'sort',
        'description', 'created_at', 'updated_at'
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
     * Валидатор входных данных для добавления нового действия
     *
     * @param array $data - данные действия
     *
     * @return bool
     * @throws CustomValidationException
     */
    public function actionValidator($data)
    {
        $validator = Validator::make(
            (array)$data,
            [
                'name' => 'required|max:255',
                'group_code' => 'required|alpha_dash|max:255',
                'module_code' => 'required|alpha_dash|max:255',
                'sort' => 'required|integer',
                'description' => 'required|max:2500',
            ]
        );

        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator,
                'Action data validation error',
                $data
            );
        } else {
            return true;
        }
    }

    /**
     * Валидатор входных данных для удаления действия
     *
     * @param array $data - данные действия
     *
     * @return bool
     * @throws CustomValidationException
     */
    public function removeActionValidator($data)
    {
        $validator = Validator::make(
            (array)$data,
            [
                'name' => 'required|max:255',
                'group_code' => 'required|alpha_dash|max:255',
                'module_code' => 'required|alpha_dash|max:255',
            ]
        );

        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator,
                'Action data validation error',
                $data
            );
        } else {
            return true;
        }
    }


    /**
     * Добавляет связь между действием и модулем
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function module()
    {
        return $this->belongsTo(
            'App\Modules\Module\Model\Module'
        );
    }

    /**
     * Добавляет связь между действием и группой пользователей
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(
            'App\Modules\User\Model\Group'
        );
    }


}
