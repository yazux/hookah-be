<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

/**
 * Класс для тестирования временного и промежуточного функционала
 *
 * @category Laravel_Сontrollers
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://lets-code.ru/
 */
class EventPusher extends BaseController
{
    
    /**
     * Инстанс объекта
     *
     * @var null
     */
    private static $_instance = null;

    private static $_config = [];

    private static $_default_channel = ['broadcast'];


    /**
     * Приватный конструктор ограничивает реализацию getInstance ()
     *
     */
    private function __construct()
    {

    }

    private static function getPusherInstance() {
        self::$_config = [
            'app_key' => env('PUSHER_APP_KEY'),
            'app_secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'encrypted' => true,
            ]
        ];
        self::$_default_channel = config('broadcasting.default_channel');
        $BindingClass = config('broadcasting.use_pusher');
        return \App::make($BindingClass, self::$_config);
    }

    public static function event($channel, $event, $options) {
        $pusher = self::getInstance();
        return $pusher->trigger($channel, $event, $options);
    }

    public static function getDefaultChannel() {
        return self::$_default_channel;
    }

    /**
     * Возвращает инстанс класса
     *
     * @return State|null
    */
    static public function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = self::getPusherInstance();
        }
        return self::$_instance;
    }

    /**
     * Ограничивает клонирование объекта
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
