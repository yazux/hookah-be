<?php

namespace App\Http\Controllers;

use App\Modules\Companies\Model\Invites;
use App\Modules\User\Controllers\UserController;
use Illuminate\Http\Response;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Interfaces\ControllerInterface;
use League\Flysystem\Exception;
use App\Exceptions\CustomException;
use App\Modules\User\Model\User;

/**
 * Класс синглтон для работы с состоянием приложения
 *
 * @category Laravel_Сontrollers
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://lets-code.ru/
 */
class State extends BaseController
{
    /**
     * Инстанс объекта
     *
     * @var null
     */
    private static $_instance = null;

    /**
     * Настройки класса
     *
     * @var array
     */
    private $_config = [];

    /**
     * Текущий пользователь
     *
     * @var
     */
    private $user = false;

    /**
     * Входящий запрос
     *
     * @var
     */
    private $request;

    /**
     * Возвращает инстанс класса
     *
     * @return State|null
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Устанавливает текущего пользователя
     *
     * @param integer $user_id - id пользователя
     *
     * @return mixed
     */
    public function setUser($user_id, $user_ip)
    {
        $user = User::where('id', $user_id)->with(['heroImage', 'groups'])->first();
        $user['ip'] = $user_ip;

        $UserController = new UserController();
        $user['isAdmin'] = $UserController->isAdmin($user->login, false);

        $Roles = []; $CompaniesToAddProduct = [];
        $Invites = Invites::where('user_id', $user->id)->where('invite', 2)->with('company')->get();
        $Companies = $user->companies()->get();
        foreach ($Invites as $invite) {
            $Roles[$invite->company_id] = $invite->role;
            if (in_array($invite->role, ['admin', 'manager'])) {
                $CompaniesToAddProduct[$invite->company->id] = $invite->company;
            }
        }
        foreach ($Companies as $company) {
            $Roles[$company->id] = 'admin';
            $CompaniesToAddProduct[$company->id] = $company;
        }
        $user->roles = $Roles;
        $user->companies_to_add_products = array_values($CompaniesToAddProduct);
        $this->user = $user;
        return $this->user;
    }

    /**
     * Возвращает текущего пользователя
     *
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Обёрткая для функции $this->getUser()
     *
     * @return mixed
     */
    public static function User() {
        $self = self::getInstance();
        return $self->getUser();
    }

    /**
     * Устанавливает параметры запроса
     *
     * @param mixed $request - массив с параметрами запроса
     *
     * @return mixed
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this->request;
    }

    /**
     * Возвращает параметры запроса
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Возвращает конфиг модуля из текущего состояния приложения
     *
     * @param string $moduleName - символьный код модуля
     *
     * @return array
     */
    public function getConfig($moduleName)
    {
        return (array_key_exists($moduleName, $this->_config)) ? $this->_config : [];
    }

    /**
     * Устанавливает конфиг модуля с символьныйм кодом $moduleName
     *
     * @param array  $config     - новый массив конфигов модуля
     * @param string $moduleName - символьный код модуля
     *
     * @return boolean
     */
    public function setConfig($config, $moduleName)
    {
        $this->_config[$moduleName] = $config;

        return true;
    }

    /**
     * Приватный конструктор ограничивает реализацию getInstance ()
     *
     */
    private function __construct()
    {
    }

    /**
     * Ограничивает клонирование объекта
     *
     */
    protected function __clone()
    {
    }
    private function __sleep()
    {
    }
    private function __wakeup()
    {
    }
    public function import()
    {

    }
    public function get()
    {

    }
}
