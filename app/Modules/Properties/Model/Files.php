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
 * Класс для работы с файлами
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class Files extends Model implements ModuleModelInterface
{

    public $table = 'module_properties_files';
    protected static $fields = [
        'id', 'user_id', 'path', 'local_path', 'created_at', 'updated_at'
    ];
    public $timestamps = true;


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


    public function uploader()
    {
        return $this->belongsTo('App\Modules\User\Model\User', 'user_id');
    }

}