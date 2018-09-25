<?php

namespace App\Http\Middleware;

use App\Modules\User\Model\User;
use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\State;
use App\Exceptions\CustomException;

/**
 * Класс Middleware для проверки администраторов
 *
 * @category Laravel_Сontrollers
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://lets-code.ru/
 */
class AdminMiddleware
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
        $User = State::User();
        if (!User::isAdmin($User->login, true))
            throw new CustomException(request()->all(), [], 403);

        return $next($request);
    }
}
