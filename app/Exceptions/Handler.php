<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use \Symfony\Component\HttpKernel\Exception\HttpException;
use \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\CustomDBException;

/**
 * Класс - обработчик всх исключений в системе
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class Handler extends ExceptionHandler
{
    /**
     * Пишет в лог о исключении
     *
     * @param Exception $exception - исключение
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Отдаёт исключение на клиент в нужном виде
     *
     * @param \Illuminate\Http\Request $request - экзепляр Request
     * @param Exception                $e       - исключение
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $e)
    {
        $statusCode = $this->getStatusCode($e);

        $result = [
            'success' => false,
            'status' => $statusCode,
            'errors' => [
                'messages' => 'Что-то пошло не так. Пожалуйста, попробуйте позже.',
                'errors'   => '',
                'file'     => ($statusCode == 500) ? $e->getFile() : '',
                'line'     => ($statusCode == 500) ? $e->getLine() : '',
                'trace'    => $e->getTrace()
            ],
            'path'     => $request->getUri(),
            'request'  => request()->all(),
            'response' => false
        ];

        if (method_exists($e, 'getMessage')) {
            $result['errors']['messages'] = $e->getMessage();
        }
        if (method_exists($e, 'getRequest')) {
            $result['request'] = request()->all();
        }
        if (method_exists($e, 'getResponse')) {
            $result['response'] = $e->getResponse();
        }

        if ($e instanceof CustomValidationException) {
            $result['errors']['errors'] = $e->validator->errors()->all();
            $result['errors']['failed'] = $e->validator->failed();
            $result['request'] = request()->all();
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            $result['errors']['errors'] = '';
            $result['errors']['failed'] = '';
            $result['errors']['messages'] = 'Что-то пошло не так. Пожалуйста, попробуйте позже.';
        }

        //return parent::render($request, $e);
        return response()->json($result, $statusCode);
    }

    /**
     * Возвращает http код, который нужно вернуть клиенту
     *
     * @param Exception $e - исключение
     *
     * @return int
     */
    protected function getStatusCode(Exception $e)
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }
        // данное исключение не является потомком
        //\Symfony\Component\HttpKernel\Exception\HttpException,
        //поэтому небольшой хак
        if ($e instanceof ModelNotFoundException) {
            return 404;
        }
        return 500;
    }

    /**
     * Возвращает сообщение исключения
     *
     * @param Exception $e - исключение
     *
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    protected function getMessage(Exception $e)
    {
        if ($e instanceof ModelNotFoundException) {
            return trans('main.model_not_found');
        }
        return trans('main.something_wrong');
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param \Illuminate\Http\Request $request   - экземпляр Request
     * @param AuthenticationException  $exception - исключение
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}
