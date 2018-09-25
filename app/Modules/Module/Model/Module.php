<?php

namespace App\Modules\Module\Model;
use App\Exceptions\Handler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\CustomValidationException;
use App\Exceptions\CustomDBException;
use League\Flysystem\Exception;
use App\Interfaces\ModuleModelInterface;

/**
 * Класс для контроля всех модулей
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class Module extends Model  implements ModuleModelInterface
{
    /**
     * Таблица
     *
     * @var string
     */
    public $table = 'modules';

    /**
     * Поля таблицы, доступные для выборки
     *
     * @var array
     */
    protected static $fields = [
        'id', 'code', 'description', 'sort',
         'active', 'created_at', 'updated_at'
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
     * Валидатор входных параметров для создания записи о новом модуле
     *
     * @param array $data - массив с данными для валидации
     *
     * @return boolean
     * @throws CustomValidationException
     */
    private function moduleValidator($data)
    {
        $validatorRules = [
            'code' => 'required|alpha_dash|max:255|unique:modules,code',
            'description' => 'required|max:500',
            'active' => 'required|boolean',
            'sort' => 'required|integer|max:255'
        ];

        //условие срабатывает, если мы пытаемся обновить запись
        if (array_key_exists('update_id', $data) && $data['update_id']) {
            $validatorRules['code'] = $validatorRules['code'].
                ','.$data['update_id'];
        }

        $validator = Validator::make((array)$data, $validatorRules);

        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator, 'validation error', $data
            );
        } else {
            return true;
        }
    }

    private function moduleUpdateValidator($data)
    {
        $validator = Validator::make(
            (array)$data,
            [
                'new_code' => 'unique:modules,code|max:255',
                'description' => 'required|max:500',
                'active' => 'required|boolean',
                'sort' => 'required|integer|max:255'
            ]
        );

        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator, 'validation error', $data
            );
        } else {
            return true;
        }
    }

    /**
     * Создаёт запись о новом модуле в таблице Modules
     *
     * @param array $data - свойства модуля
     *
     * @return array|bool
     */
    public function createModule($data)
    {
        if (!array_key_exists('sort', $data) || !$data['sort']) {
            $data['sort'] = 100;
        }
        $validate = $this->ModuleValidator($data);
        if ($validate) {
            $newModule = new Module();
            $newModule->code = $data['code'];
            $newModule->description = $data['description'];
            $newModule->sort = $data['sort'];
            $newModule->active = ($data['active']) ? true : false;
            $newModule->save();
            return true;
        }

        return false;
    }

    /**
     * Обновляет параметры модуля с кодом, переданным в $data
     *
     * @param array $data - массив с новыми параметрами модуля
     *
     * @return bool
     * @throws CustomDBException
     */
    public function putModuleByCode( $data )
    {

        $ExistModule = $this::where('code', $data['code'])->first();
        if (!$ExistModule) {
            throw new CustomDBException(
                $data, [], 404, 'Module with code "'.$data['code'].'" is not found'
            );
        }

        $data['update_id'] = $ExistModule['id'];

        if (array_key_exists('new_code', $data) && $data['new_code']) {
            $data['code'] = $data['new_code'];
        }
        unset($data['new_code']);


        $validate = $this->moduleValidator($data);
        if ($validate) {
            unset($data['update_id']);
            $Module = $this::where('id', $ExistModule['id'])->update($data);
            return $Module;
        }
        return false;
    }

    /**
     * Удаляет запись о модуле по его коду
     *
     * @param string $code - символьный код модуля
     *
     * @return bool
     */
    public function removeModuleByCode($code)
    {
        return ($this::where('code', $code)->delete()) ? true : false ;
    }

    /**
     * Возвращает список всех модулей
     *
     * @return mixed
     */
    public function getModules()
    {
        return $this::get();
    }

    /**
     * Возвращает модуль по его коду
     *
     * @param string $code - символьный код модуля
     *
     * @return mixed
     * @throws CustomDBException
     */
    public function getModuleByCode( $code, $data = [])
    {
        $Module = $this::where('code', $code)->first();
        if (count($Module)) {
            return $Module;
        }
        throw new CustomDBException($data, [], 404, "Module '".$code."' not found");
    }

}
