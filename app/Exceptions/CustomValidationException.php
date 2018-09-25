<?php

namespace App\Exceptions;

use Exception;
use App\Interfaces\ExceptionInterface;

class CustomValidationException extends Exception implements ExceptionInterface
{
    /**
     * Экземпляр класса \Illuminate\Support\Facades\Validator;
     *
     * @var \Illuminate\Support\Facades\Validator;
     */
    public $validator;

    /**
     * Текстовый ответ, который отправится клиенту
     *
     * @var \Symfony\Component\HttpFoundation\Response|null
     */
    public $response;

    /**
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
     * CustomValidationException constructor.
     *
     * @param string $validator
     * @param null   $response
     * @param int    $statusCode
     * @param string $message
     */
    public function __construct($validator, $response = null, $request = [], $statusCode = 400, $message = '')
    {
        parent::__construct( ($message || $message != '') ? $message : 'Ошибка проверки входных данных.');

        $this->request    = $request;
        $this->response   = $response;
        $this->validator  = $validator;
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
}
