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
 * Класс для работы с отношениями между пользователями и группами
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class UsersToGroup extends Model  implements ModuleModelInterface
{
    /**
     * Имя используемой таблицы
     *
     * @var string
     */
    public $table = 'module_users_to_group';

    /**
     * Поля таблицы, доступные для выборки
     *
     * @var array
     */
    protected static $fields = [
        'id', 'user_id', 'group_id'
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
}
