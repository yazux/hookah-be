<?php

namespace App\Classes;

use App\Exceptions\CustomException;
use League\Flysystem\Exception;
use Moneta\MonetaWebService;
use Moneta\Types\FindAccountsListRequest;

/**
 * Базовый синглтон для работы с сервисом Moneta.ru
 * @package App\Classes
 */
class MonetaBase implements MonetaInterface
{
    /**
     * Массив инстансов
     * @var array
     */
    protected static $_instance = [];

    /**
     * Ограничивает реализацию getInstance ()
     */
    protected function __construct() {}

    /**
     * Ограничивает клонирование объекта
     */
    protected function __clone() {}

    /**
     * Создаёт инстанс класса
     *
     * @param bool $sClassName
     *
     * @return bool|mixed
     */
    static public function getInstance($sClassName = false) {

        if(!$sClassName) $sClassName = get_called_class();

        if (class_exists($sClassName)) {
            if (!isset(self::$_instance[$sClassName])) {
                self::$_instance[ $sClassName ] = new $sClassName();
            }
            return self::$_instance[$sClassName];
        }

        return false;
    }

    /**
     * Обёртка для функции self::getInstance()
     *
     * @param bool $sClassName
     *
     * @return bool|mixed
     */
    static public function gi($sClassName = false) {
        return self::getInstance($sClassName);
    }

    /**
     * Экземпляр MonetaWebService
     *
     * @var null|MonetaWebService
     */
    protected static $_service = null;

    /**
     * Создаёт новое подключение к сервису Moneta.ru
     *
     * @return MonetaWebService|null
     * @throws CustomException
     */
    public static function getService()
    {
        if (!self::$_service) {
            try {
                self::$_service = new MonetaWS(
                    env('MONETA_WSDL',  'https://demo.moneta.ru/services.wsdl'),
                    env('MONETA_LOGIN', 'nco@gidexp.ru'),
                    env('MONETA_PASS',  'nco@2gid')
                );
            } catch (Exception $e) {
                throw new CustomException(
                    [], [], 500, 'Ошибка подключения к ' .
                    'сервису Moneta.ru: ' . $e->getMessage()
                );
            }
        }
        return self::$_service;
    }

    /**
     * Обёртка для функции self::getService()
     *
     * @return MonetaWebService|null
     */
    public static function gs()
    {
        return self::getService();
    }

    /**
     * Обёртка для функции pushArgumentsToRequest
     * Добавляет переданные данные как аргументы
     * в переданный класс
     *
     * @param null|object  $requestObject    - Инстанс класса - запроса из файла /app/Classes/Moneta/MonetaDataTypes.php
     * @param array        $requireArguments - Массив ключей, которы обязательно должен содержать массив $arguments
     * @param array        $arguments        - Массив с аргументами ['key1' => 'val1', 'key2' => 'val2']
     *
     * @return null|object|array
     */
    public static function PATR(
        $requestObject = null, $requireArguments = [], $arguments = []
    ) {
        return self::pushArgumentsToRequest($requestObject, $requireArguments, $arguments);
    }

    /**
     * Добавляет переданные данные как аргументы
     * в переданный класс
     *
     * @param null|object  $requestObject    - Инстанс класса - запроса из файла /app/Classes/Moneta/MonetaDataTypes.php
     * @param array        $requireArguments - Массив ключей, которы обязательно должен содержать массив $arguments
     * @param array        $arguments        - Массив с аргументами ['key1' => 'val1', 'key2' => 'val2']
     *
     * @return null|object
     * @throws CustomException
     */
    public static function pushArgumentsToRequest(
        $requestObject = null, $requireArguments = [], $arguments = []
    ) {

        if (!$requireArguments || !$arguments
            || !count($requireArguments) || !count($arguments)
        ) {
            throw new CustomException(
                [], [], 500, 'Для добавления аргументов требуется '
                . 'передавать ассоциативные массивы в параметрах '
                . '"$requireArguments" и "$arguments".'
            );
        }

        if (!is_object($requestObject)) {
            throw new CustomException(
                [], [], 500, 'Для добавления аргументов требуется '
                . 'передать инстанс класса в параметре "$request".'
            );
        }

        foreach ($requireArguments as $requireArgument) {
            if (!array_key_exists($requireArgument, $arguments)) {
                throw new CustomException(
                    [], [], 500, 'Для класса "' . get_class($requestObject) .
                    '" не указан обязательный параметр "'  .
                    $requireArgument . '"'
                );
            }
        }

        foreach ($arguments as $key => $value) {
            if (property_exists($requestObject, $key)) {
                $requestObject->{$key} = $value;
            }
        }

        return $requestObject;
    }

    /**
     * Выполняет запрос к сервису, если вылетает исключение,
     * то выбрасывает его в форматированном виде
     *
     * @param string $method    - Метод для вызова
     * @param mixed  $arguments - Массив параметров
     *
     * @return mixed
     * @throws CustomException
     */
    public static function request($method, $arguments)
    {
        try {
            return self::gs()->{$method}($arguments);
        } catch (Exception $e) {
            throw new CustomException(
                ['arguments' => $arguments], [], 500,
                'Ошибка выполнения запроса к сервису moneta.ru: ' . $e->getMessage()
            );
        }
    }
}
