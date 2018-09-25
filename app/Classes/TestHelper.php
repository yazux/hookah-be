<?php

namespace App\Classes;

use App\Modules\User\Controllers\UserController;
use App\Modules\User\Model\User;
use GuzzleHttp\Client as HttpClient;

/**
 * Хелпер для тестов
 *
 * @package App\Classes
 */
class TestHelper
{

    public static $user = [
        'login' => 'unitTestUser',
        'email' => 'user@unit.ru',
        'password' => 'Bb103ecc',
        'password_confirm' => 'Bb103ecc',
    ];

    public static $default_user = [
        'login' => 'unitTestUser',
        'email' => 'user@unit.ru',
        'password' => 'Bb103ecc',
        'password_confirm' => 'Bb103ecc',
    ];

    public static $user_admin = [
        'login'      => 'admin',
        'email'      => 'admin',
        'password'   => 'Bb103ecc',
        'password_confirm' => 'Bb103ecc',
        'login_auth' => 'true'
    ];

    public static $self = null;

    /**
     * TestHelper constructor.
     */
    private function __construct()
    {
    }

    /**
     * Выводит в консоль контент теста
     *
     * @param \TestCase $test - объект теста
     *
     * @return null
     */
    public static function debug($test)
    {
        print_r($test->response->getContent());
    }

    /**
     * Возвращает инстанс
     *
     * @return TestHelper|null
     */
    public static function gi()
    {
        if (!self::$self) {
            self::$self = new self();
        }

        return self::$self;
    }

    /**
     * Создаёт нового пользователя
     *
     * @param \TestCase|boolean $test - объект теста
     *
     * @return static
     */
    public static function user($test = false)
    {
        $user = self::$user;
        $UserController = new UserController();
        $user['password'] = $UserController
            ->encryptPassword($user['password']);

        $User = User::create($user);
        $User->groups()->attach(2);
        $User->password = self::$user['password'];

        self::$user = $User;

        if ($test) {
            $test->assertTrue(
                (
                    isset($User['id'])    && isset($User['login']) &&
                    isset($User['email']) && isset($User['password'])
                )
            );
        }

        return json_decode(json_encode($User), true);
    }

    /**
     * Удаление пользователя из БД
     *
     * @param bool $user - массив пользователя
     *
     * @return null
     */
    public static function removeUser($user = false)
    {
        self::$user = self::$default_user;
        if (!$user) {
            $user = self::$user['email'];
        }
        User::where('email', $user)->delete();
    }

    /**
     * Авторизует тестового пользователя
     *
     * @param array $user - Массив пользователя ['login' => '', 'email' => '']
     * @param \TestCase|boolean $test - объект теста
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    public static function auth($user, $test = false)
    {
        if (!$user) {
            $user = self::$user;
        }
        $user['login_auth'] = isset($user['login_auth']) ? $user['login_auth'] : false;
        $client = new HttpClient();
        $res = $client->request(
            'POST',
            env('APP_URL') . '/api/login',
            [
                'form_params' => [
                    'login'      => $user['email'],
                    'password'   => $user['password'],
                    'login_auth' => $user['login_auth']
                ]
            ]
        );

        $result = $res->getBody();
        if (!is_array($result)) {
            $result = json_decode($result, true);
        }

        if ($test) {
            $test->assertTrue(isset($result['response']['token_data']['token']));
        }

        return $result;
    }
}