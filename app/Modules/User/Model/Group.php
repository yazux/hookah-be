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
 * Класс для работы с группами пользователей
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class Group extends Model implements ModuleModelInterface
{
    /**
     * Имя используемой таблицы
     *
     * @var string
     */
    public $table = 'module_users_groups';

    /**
     * Поля таблицы, доступные для выборки
     *
     * @var array
     */
    protected static $fields = [
        'id', 'name', 'description', 'sort', 'code', 'created_at', 'updated_at'
    ];


    public $fillable = [
        'id', 'name', 'description', 'sort', 'code', 'created_at', 'updated_at'
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
     * Вернёт пользователей с данной группой
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            'App\Modules\User\Model\User',
            'module_users_to_group', 'user_id', 'group_id'
        );
    }

    /**
     * Валидатор входных данных для добавления
     * и обновления групп
     *
     * @param array $data - данные группы
     *
     * @return bool
     * @throws CustomValidationException
     */
    public static function groupValidator($data)
    {

        $validatorRules = [
            'name' => 'required|max:255',
            'description' => 'required|max:2500',
            'code' => 'required|alpha_dash|unique:module_users_groups,code',
            'sort' => 'required|integer'
        ];

        //условие срабатывает, если мы пытаемся обновить запись
        if (array_key_exists('update_id', $data) && $data['update_id']) {
            $validatorRules['code'] = $validatorRules['code'].','.$data['update_id'];
        }

        $validator = Validator::make($data, $validatorRules);

        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator,
                'Group data validation error',
                $data
            );
        } else {
            return true;
        }
    }


}
