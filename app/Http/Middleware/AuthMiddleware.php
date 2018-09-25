<?php

namespace App\Http\Middleware;

use Closure;
use Redis;
use Illuminate\Http\Request;
use App\Http\Controllers\State;
use App\Exceptions\CustomException;
use App\Modules\User\Controllers\AuthController as Auth;

/**
 * Класс Middleware для проверки авторизации пользователей
 *
 * @category Laravel_Сontrollers
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://lets-code.ru/
 */
class AuthMiddleware
{
    /**
     * Обработчик входящих запросов
     *
     * @param Request $request - экземпляр Request
     * @param Closure $next    - экземпляр Closure
     *
     * @return mixed
     * @throws CustomException
     */
    public function handle($request, Closure $next)
    {
        $defErrTxt = 'Попробуйте перезагрузить страницу и авторизоваться снова.';
        $data  = $request->all();
        $token = $request->header('authorization');
        if (!$token) {
            if (isset($data['authorization'])) {
                $token = $data['authorization'];
            }
        }
        $RequestData['input_auth_token'] = $token;
        $RequestData['redis_auth_token'] = '';

        //Достаём данные пользователя из Redis по ключу - токену
        try {
            $redis = app()->make('redis');
            $RequestData['redis_auth_token'] = $redis->get(
                'auth_token:'.$RequestData['input_auth_token']
            );
        } catch (Exception $e) {
            throw new CustomException($request, [], 500, 'Ошибка соединения с сервером Redis. ' . $defErrTxt);
        }

        //проверим, нашли ли мы токен в Redis
        if (!$RequestData['redis_auth_token']
            || $RequestData['redis_auth_token'] == null
        ) {
            throw new CustomException($request, [], 401, 'Токен не найден. ' . $defErrTxt);
        }

        //парсим запись из Redis
        //(так как данные хранятся одной строкой с разделителем)
        $RequestData['redis_data'] = explode(':', $RequestData['redis_auth_token']);
        $RequestData['redis_data'] = [
            'user_id'          => $RequestData['redis_data'][0],
            'ip'               => $RequestData['redis_data'][1],
            'token_start_time' => $RequestData['redis_data'][2],
            'refresh_token'    => $RequestData['redis_data'][3]
        ];
        $RequestArray = $request->all();
        //если пользователь обратился с другого ip адреса, то скидываем авторизацию
        //и сообщаем пользователю о том,
        //что возможно его авторизация скомпроментирована
        /*
        if (!isset($data['unit_test'])) {
            if ($RequestData['redis_data']['ip'] != $request->ip()) {
                $redis->del('auth_token:' . $RequestData['input_auth_token']);
                throw new CustomException(
                    $request, [], 401, 'IP адресс был скомпроментирован! ' . $defErrTxt
                );
            }
        }

        //если время жизни токена кончилось
        $TokenLiveTime = (time() - $RequestData['redis_data']['token_start_time']);
        if ($TokenLiveTime >= env('TOKEN_LIVE_TIME')
            && $request->path() == env('REFRESH_TOKEN_PATH')
        ) {
            //если пользователь пытается обновить токен и
            //он прислал в запросе refresh_token то пропускаем дальше
            if (!array_key_exists('refresh_token', $RequestArray)) {
                throw new CustomException(
                    $request, [], 400, 'Токен для обновления не найден. ' . $defErrTxt
                );
            }
        }
        */

        /*//если время жизни токена кончилось и пользователь не обновляет его
        if ($request->path() != env('REFRESH_TOKEN_PATH')
            && $TokenLiveTime >= env('TOKEN_LIVE_TIME')
        ) {
            throw new CustomException($request, [], 401, 'Время жизни токена истекло. ' . $defErrTxt);
        }*/

        //если пользователь пытается обновить токен
        if ($request->path() == env('REFRESH_TOKEN_PATH')) {

            if (!array_key_exists('refresh_token', $RequestArray)) {
                throw new CustomException(
                    $request, [], 400, 'Токен для обновления не найден. ' . $defErrTxt
                );
            }

            //проверим, совпал ли входящий refresh_token,
            //с тем что хранится в redis
            if ($RequestData['redis_data']['refresh_token'] != $RequestArray['refresh_token']) {
                //если не совпал, считаем что авторизация скомпроментирована
                //удалям токен
                $redis->del('auth_token:'.$RequestData['input_auth_token']);
                throw new CustomException(
                    $request, [], 401, 'IP адресс был скомпроментирован! ' . $defErrTxt
                );
            }
            //удаляем старый токен из Redis
            $redis->del('auth_token:'.$RequestData['input_auth_token']);
            //дописываем id пользователя в запрос
            //и отправляем пользователя дальше, на обновление пароля
            $request->request->add(
                ['user_id' => $RequestData['redis_data']['user_id']]
            );
            return $next($request);
        }

        //установим текущего пользователя в состоянии приложения
        $State = State::getInstance();
        $State->setUser($RequestData['redis_data']['user_id'], $request->ip());

        return $next($request);
    }
}
