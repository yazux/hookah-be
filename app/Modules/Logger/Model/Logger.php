<?php
namespace App\Modules\Logger\Model;

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
 * Класс для работы с логами
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class Logger extends Model implements ModuleModelInterface
{

    /**
     * Имя используемой таблицы
     *
     * @var string
     */
    public $table = 'module_logger';

    /**
     * Поля таблицы, доступные для выборки
     *
     * @var array
     */
    protected static $fields = [
        'id',
        'module_id',
        'user_id',
        'user_ip',
        'log_type',
        'log_action',
        'description',
        'created_at',
        'updated_at',
        'entity_type',
        'entity_id'
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
     * Связь с пользователями
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(
            'App\Modules\User\Model\User'
        );
    }

    /**
     * Связь с модулями
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
     * Связь с компанией
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(
            'App\Modules\Companies\Model\Company'
        );
    }
}