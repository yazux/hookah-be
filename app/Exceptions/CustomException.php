<?php

namespace App\Exceptions;

use Exception;
use App\Interfaces\ExceptionInterface;

class CustomException extends Exception implements ExceptionInterface
{
    /**
     * Текстовый ответ, который отправится клиенту
     *
     * @var \Symfony\Component\HttpFoundation\Response|null
     */
    public $response;

    public static $errorTest = [
        '401' => 'Для доступа требуется авторизация',
        '403' => 'У пользователя нет прав на выполнение действия',
        '404' => 'Искомая запись не найдена',
        '500' => 'Произошла ошибка, пожалуйста попробуйте позже, если ошибка повторится, то обратитесь к администратору'
    ];

    /**
     *
     * @var array
     */
    public $request;

    /**
     * Код ответа сервера, который отправится клиенту
     *
     * @var int
     */
    public $statusCode;

    /**
     * DataBaseException constructor.
     *
     * @param array $request
     * @param array $response
     * @param int $statusCode
     * @param string $message
     */
    public function __construct($request = [], $response = [], $statusCode = 500, $message = '')
    {
        $message = (!$message || $message == '') ? self::text($statusCode) : $message;
        parent::__construct($message);
        $this->request = $request;
        $this->response = $response;
        $this->statusCode = $statusCode;
    }

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Возвращает переданный в конструктор, текстовый ответ, который отправится клиенту
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Возвращает переданный в конструктор, код ответа сервера, который отправится клиенту
     *
     * @return int
     */
    public function getStatusCode(){
        return $this->statusCode;
    }

    public static function text($key) {
        return (isset(self::$errorTest[$key])) ? self::$errorTest[$key] : '';
    }
}
