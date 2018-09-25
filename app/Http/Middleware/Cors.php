<?php

namespace App\Http\Middleware;

use App\Http\Controllers\State;
use Closure;
use Illuminate\Http\Request;
use App\Exceptions\CustomException;

/**
 * Класс Middleware для проверки CORS запросов
 *
 * @category Laravel_Сontrollers
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://lets-code.ru/
 */
class Cors
{

    /**
     * Хендл
     *
     * @param Request $request - экземлпяр Request с запросом от клиента
     * @param Closure $next    - ссылка для отправки запроса дальше
     *
     * @return mixed
     * @throws CustomException
     */
    public function handle($request, Closure $next)
    {
        $token = $request->header('authorization');
        //если пользователь авторизованный
        //устанавливаем его в инстанс
        if ($token) {
            //Достаём данные пользователя из Redis по ключу - токену
            try {
                $redis      = app()->make('redis');
                $redis_data = $redis->get('auth_token:' . $token);
            } catch (\Exception $e) {
                throw new CustomException(
                    $request, [], 500, 'Ошибка соединения с базой Redis'
                );
            }

            //если авторизация пользователя ещё есть
            if ($redis_data) {
                //парсим запись из Redis
                //(так как данные хранятся одной строкой с разделителем)
                $redis_data = explode(':', $redis_data);
                $redis_data = [
                    'user_id' => $redis_data[0],
                    'ip' => $redis_data[1],
                    'token_start_time' => $redis_data[2],
                    'refresh_token' => $redis_data[3]
                ];
                //установим текущего пользователя в состоянии приложения
                $State = State::getInstance();
                $State->setUser($redis_data['user_id'], $request->ip());
            }
        }

        //если просят доки по API, то отдаём всем
        if (env('SWAGGER_URL') == $request->path()) {
            $allowOrigin = '*';
        } else {
            //иначе только проверенным
            //тут потом нужно будет дописать проверку через заголовок ORIGIN
            //$allowOrigin = 'http://localhost:8080';
            $allowOrigin = '*';
        }

        //$allowOrigin = 'https://igid24.ru';
        return $next($request)
            ->header('Access-Control-Allow-Origin', $allowOrigin)
            //->header('Access-Control-Allow-Origin', '*')
            ->header(
                'Access-Control-Allow-Methods',
                'GET, POST, PUT, DELETE, OPTIONS, HEAD'
            )
            ->header(
                'Allow',
                'GET, POST, PUT, DELETE, OPTIONS, HEAD'
            )
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header(
                'Access-Control-Allow-Headers',
                'Authorization, Content-Type, Origin, api_key, X-Requested-With, '.
                'X-Auth-Token, token, Accept, X-PINGOTHER, X-ACCESS_TOKEN, ' .
                'Access-Control-Request-Method, Access-Control-Request-Headers, ' .
                'Access-Control-Allow-Origin'
            )
            ->header('Content-type', 'application/json; charset=utf-8')
            ->header(
                'Access-Control-Expose-Headers',
                'X-Pagination-Current-Page, X-Pagination-Page-Count, ' .
                'X-Pagination-Per-Page, X-Pagination-Total-Count'
            );

        /*
        return $next($request)
            ->header('Access-Control-Allow-Origin',      $allowOrigin)
            ->header('Access-Control-Allow-Methods',     '*')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Headers',     '*')
            ->header('Access-Control-Expose-Headers',    '*')
            ->header('Allow',  '*')
            ->header('Accept', '/*')
            ->header('Accept-Language', '*')
            ->header('Content-type',    '/*');


        $serv = $request->server();
        $accToken = 'hwgJUWNcG8HhHU88epXI';
        $accessHost = env('ACCESS_HOST');
        if (array_key_exists('HTTP_ORIGIN', $serv)) {
            $host = $serv['HTTP_ORIGIN'];

            if ($host == $accessHost) {
                return $next($request)
                    ->header('Access-Control-Allow-Origin', $accessHost)
                    ->header(
                        'Access-Control-Allow-Methods',
                        'GET, POST, PUT, DELETE'
                    )
                    ->header('Access-Control-Allow-Credentials', 'true')
                    ->header(
                        'Access-Control-Allow-Headers',
                        'Authorization, Origin, X-Requested-With, '.
                        'Accept, X-PINGOTHER, Content-Type'
                    );

            } else {
                return redirect($accessHost.'/');
            }
        } else {
            if ((array_key_exists('token', $_GET) && $_GET['token'] == $accToken )
                || (array_key_exists('token', $_POST) && $_POST['token'] == $accToken)
            ) {
                return $next($request)
                    ->header('Access-Control-Allow-Origin', $accessHost)
                    ->header(
                        'Access-Control-Allow-Methods',
                        'GET, POST, PUT, DELETE'
                    )
                    ->header('Access-Control-Allow-Credentials', 'true')
                    ->header(
                        'Access-Control-Allow-Headers',
                        'Authorization, Origin, X-Requested-With, '.
                        'Accept, X-PINGOTHER, Content-Type'
                    );

            } else {
                return redirect($accessHost.'/');
            }
        }

        */

    }
}
