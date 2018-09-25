<?php

namespace App\Exceptions;

use Exception;
use App\Interfaces\ExceptionInterface;

class CustomDBException extends Exception implements ExceptionInterface
{
    /**
     * Текстовый ответ, который отправится клиенту
     *
     * @var \Symfony\Component\HttpFoundation\Response|null
     */
    public $response;

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
     * @param null $response
     * @param int $statusCode
     * @param string $message
     */
    public function __construct($request = [], $response = null, $statusCode = 500, $message = '')
    {
        parent::__construct( 'Data base exception: '.$message );
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
}
