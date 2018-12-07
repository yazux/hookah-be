<?php
namespace App\Modules\User\Model;

use App\Modules\User\Controllers\UserController;
use App\Exceptions\CustomValidationException;
use App\Exceptions\CustomDBException;
use App\Classes\BaseModel;

/**
 * Класс для работы с пользователями
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class User extends BaseModel
{
    public $table = 'module_users';

    public $fillable = [
        'id',
        'login',
        'email',
        'sort',
        'password',
        'rating',
        'name',
        'surname',
        'middle_name',
        'phone',
        'birthday',
        'address',
        'hero_image_id',
        'email_confirm',
        'email_code',
        'created_at',
        'updated_at'
    ];

    public $rules = [
        'login'       => 'required|alpha_dash|max:255|unique:module_users,login',
        'email'       => 'required|email|max:255|unique:module_users,email',

        'sort'        => 'integer|nullable',
        'hero_image_id'  => 'nullable|integer|min:1',
        'name'        => 'nullable|alpha|min:1|max:100',
        'surname'     => 'nullable|alpha|min:1|max:100',
        'middle_name' => 'nullable|alpha|min:1|max:100',
        'phone'       => 'nullable|numeric|min:1|max:99999999999',
        'birthday'    => 'nullable|min:1|max:255',
        'address'     => 'nullable|min:1|max:255',
        'email_confirm' => 'nullable|integer|min:0|max:9',
        'email_code'    => 'nullable|min:1|max:100'
    ];

    public $messages = [
        'login.required'   => 'Требуется указать Логин',
        'login.alpha_dash' => 'Логин может состоять только из латинских букв и цифр',
        'login.max'        => 'Превышена максимальная длина логина',
        'login.unique'     => 'Такой логин пользователя уже занят',

        'email.required'   => 'Требуется указать Email',
        'email.email'      => 'Email должен быть корректным email адресом',
        'email.max'        => 'Превышена максимальная длина Email',
        'email.unique'     => 'Такой Email пользователя уже занят',

        'phone.numeric'    => 'Номер телефона может состоять только из цифр',
        'phone.max'        => 'Превышена максимальная длина номера телефона',

        'name.alpha'        => 'ФИО должно содержать только алфавитные символы',
        'surname.alpha'     => 'ФИО должно содержать только алфавитные символы',
        'middle_name.alpha' => 'ФИО должно содержать только алфавитные символы',
    ];


    /**
     * Проверят может ли текущий
     * пользователь выполнять действие $action
     *
     * @param string $action         - символьный код действия
     * @param bool   $ThrowException - выбрасывать ли исключение
     *
     * @return bool
     *
     * @throws CustomDBException
     */
    public static function can($action, $ThrowException = false)
    {
        return UserController::can($action, $ThrowException);
    }

    /**
     * Обёртка для функции isAdmin у UserController
     *
     * @param string $login - логин пользователя
     * @param bool   $json  - формат ответа
     *
     * @return bool|mixed
     */
    public static function isAdmin($login = null, $json = false)
    {
        $UC = new UserController();
        return $UC->isAdmin($login, $json);
    }

    /**
     * Валидатор входных данных для добавления нового пользователя
     *
     * @param array $data - данные пользователя
     *
     * @return bool
     * @throws CustomValidationException
     */
    public static function userValidator($data)
    {
        return self::valid($data);
    }

    /**
     * Связь с группами пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(
            'App\Modules\User\Model\Group',
            'module_users_to_group', 'user_id', 'group_id'
        );
    }

    /**
     * Связь с компаниями, в которых есть пользователь
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function companies()
    {
        return $this->belongsToMany(
            'App\Modules\Companies\Model\Company',
            'module_companies_users', 'user_id', 'company_id'
        );
    }


    /**
     * Связь с инвайтами
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invites()
    {
        return $this->hasMany('App\Modules\Companies\Model\Invites', 'user_id');
    }

    /**
     * Связь с изображением
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function heroImage()
    {
        return $this->belongsTo('App\Modules\Properties\Model\Files', 'hero_image_id');
    }
}
