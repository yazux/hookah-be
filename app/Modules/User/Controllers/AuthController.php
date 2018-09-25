<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Model\User;

use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Exceptions\CustomDBException;

use App\Modules\User\Controllers\UserController;

use Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

/**
 * Класс авторизации пользователей
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class AuthController extends Controller implements ModuleInterface
{

    /**
     * Код модуля
     *
     * @var string
     */
    public $moduleName = 'User';

    /**
     * Вернёт код модуля
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }


    /**
     * Авторизует пользователя по логину и паролю
     * в login_auth передаётся 0 или 1:
     * если 0 - авторизация по email
     * если 1 - авторизация по логину
     *
     * @param Request $request - Экземпляр Request
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function login( Request $request )
    {
        $data = $request->only(['login', 'password', 'login_auth']);

        if (!array_key_exists('login_auth', $data) || $data['login_auth'] == null) {
            $data['login_auth'] = false;
        }

        //проверим тип авторизации и найдём пользователя для авторизации
        if ($data['login_auth']) {
            $UserData = User::where('login', $data['login'])->first();
            //если пользователя не нашли
            if (!$UserData || $UserData == null) {
                throw new CustomDBException(
                    $data, $UserData, 404,
                    'Пользователь с логином "'.$data['login'].'" не найден'
                );
            }
        } else {
            $UserData = User::where('email', $data['login'])->first();
            //если пользователя не нашли
            if (!$UserData || $UserData == null) {
                throw new CustomDBException(
                    $data, $UserData, 404,
                    'Пользователь с email "'.$data['login'].'" не найден'
                );
            }
        }



        //проверяем правильный ли пароль ввёл пользователь
        $UserController = new UserController();
        $CheckPassword = $UserController->checkPassword(
            $data['password'], $UserData['password']
        );

        if (!$CheckPassword) {
            throw new CustomException(
                $data, [], 400,
                'Логин/Email или пароль указанны не верно'
            );
        }

        $UserData['token_data'] = $this->setToken($UserData['id'], $request);

        return parent::response($data, $UserData, 200);
    }

    /**
     * Снимает авторизацию пользователя
     *
     * @param Request $request -Экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function logout(Request $request)
    {
        $IsRemoveToken = $this->removeToken($request);
        if (!$IsRemoveToken) {
            throw new CustomException($request, [], 500, 'Remove token error');
        }
        $result = [
            'auth' => false,
            'redirect_url' => '/'
        ];
        return parent::response($request->path(), $result, 200);
    }

    /**
     * Возвращает данные по токену из Redis хранилища
     *
     * @param Request $request - Экземпляр Request
     *
     * @return array
     * @throws CustomException
     */
    public function getTokenData($request)
    {
        $InputAuthToken = $request->header('authorization');
        //Достаём данные пользователя из Redis по ключу - токену
        try {
            $redis = app()->make('redis');
            $RedisData = $redis->get('auth_token:'.$InputAuthToken);
        } catch (Exception $e) {
            throw new CustomException($request, [], 500, 'Redis connect error');
        }

        //проверим, нали ли мы токен в Redis
        if (!$RedisData || $RedisData == null) {
            throw new CustomException($request, [], 401, 'Bad access token');
        }

        //парсим запись из Redis
        $RedisData = explode(':', $RedisData);
        $RedisData = [
            'user_id'          => $RedisData[0],
            'ip'               => $RedisData[1],
            'token_start_time' => $RedisData[2],
            'refresh_token'    => $RedisData[3]
        ];

        return $RedisData;
    }

    /**
     * Удаляет данные по токену из Redis хранилища
     *
     * @param Request $request - Экземпляр Request
     *
     * @return bool
     * @throws CustomException
     */
    public function removeToken($request)
    {
        $InputAuthToken = $request->header('authorization');
        //Достаём данные пользователя из Redis по ключу - токену
        try {
            $redis = app()->make('redis');
            $RedisData = $redis->get('auth_token:'.$InputAuthToken);
        } catch (Exception $e) {
            throw new CustomException($request, [], 500, 'Redis connect error');
        }
        //проверим, нали ли мы токен в Redis
        if (!$RedisData || $RedisData == null) {
            throw new CustomException($request, [], 401, 'Bad access token');
        }

        $redis->del('auth_token:'.$InputAuthToken);
        return true;
    }

    /**
     * Обновляет токен с истечённым сроком действия
     * Вернёт новый access token и refresh token
     *
     * @param Request $request - Экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function refreshToken(Request $request)
    {
        $RequestData = $request->all();

        if (!array_key_exists('user_id', $RequestData)) {
            throw new CustomException(
                $RequestData, [], 400, 'user_id is not defined'
            );
        }

        $TokenData = $this->setToken($RequestData['user_id'], $request);

        return parent::response($RequestData, $TokenData, 200);
    }

    /**
     * Записывает в Redis хранилище id авторизовавшегося пользователя,
     * ip адрес и время авторизации
     *
     * @param integer $UserId  - id пользователя
     * @param Request $request - Экземпляр Request
     *
     * @return mixed
     */
    public function setToken( $UserId, $request )
    {
        $Data['token'] = $this->generateAuthToken();
        $Data['refresh_token'] = $this->generateAuthToken();

        $redis = app()->make('redis');
        $redis->set(
            'auth_token:'.$Data['token'],
            $UserId.':'.$request->ip().':'.time().':'.$Data['refresh_token']
        );
        $Data['redis'] = $redis->get('auth_token:'.$Data['token']);

        return $Data;
    }

    /**
     * Генерирует набор случайных чисел
     * Используется в качестве генератора токенов
     *
     * @return string
     */
    public function generateAuthToken()
    {
        return base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    }

}