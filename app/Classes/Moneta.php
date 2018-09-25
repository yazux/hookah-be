<?php

namespace App\Classes;

/**
 * Фабрика для работы с сервисом Moneta.ru
 * @package App\Classes
 */
class Moneta
{
    /**
     * Инстанс класса
     *
     * @var Moneta
     */
    public static $instance = null;

    /**
     * Инстанс класса MonetaBase
     *
     * @var MonetaBase
     */
    public $moneta  = null;

    /**
     * Инстанс класса MonetaAccount
     *
     * @var MonetaAccount
     */
    public $account = null;


    /**
     * Ограничивает реализацию getInstance ()
     */
    protected function __construct() {}

    /**
     * Ограничивает клонирование объекта
     */
    protected function __clone() {}

    /**
     * Создаёт инстанс объекта
     *
     * @return Moneta|null
     */
    protected static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Обёртка для функции self::getInstance()
     *
     * @return Moneta|null
     */
    protected static function gi()
    {
        return self::getInstance();
    }

    /**
     * Возвращает экземпляр объекта MonetaAccount
     *
     * @return MonetaAccount
     */
    public static function account()
    {
        if (!self::gi()->account) {
            self::gi()->account = MonetaAccount::gi();
        }

        return self::gi()->account;
    }

    //создание пльзователя
    //удаление пльзователя
    //изменение пльзователя
    //получение пльзователя
    //поиск пльзователя

    //создание счёта
    //удаление счёта
    //запрос баланса счёта
    //получение данных о счёте

    //перевод денег
    //блокирование денег
    //разблокирование денег
}