<?php

namespace App\Classes;

use Moneta\Types\CreateAccountRequest;
use Moneta\Types\CreateBankAccountRequest;
use Moneta\Types\EditBankAccountRequest;
use Moneta\Types\FindAccountsListRequest;

/**
 * Класс для работы с счетами в сервисе Moneta.ru
 * @package App\Classes
 */
class MonetaAccount extends MonetaBase implements MonetaInterface
{

    public static function createAccount($unitId = null, $pass = null, $signature = null)
    {
        $request = parent::PATR(new CreateAccountRequest(), ['unitId'], [
            'unitId'              => ($unitId) ? $unitId : env('PROFILE_UNIT_ID', 45316),
            "currency"            => "RUB",
            "paymentPasswordType" => "STATIC",
            "paymentPassword"     => $pass,
            "signature"           => $signature,
            "prototypeAccountId"  => env('MONETA_PROTOTYPE_ACCOUNT', '67891567')
        ]);
        return parent::request('CreateAccount', $request);
    }

    /**
     * Возввращает основной счёт в Moneta.ru
     *
     * @return object
     */
    public static function main()
    {
        return self::byId(env('MONETA_PROTOTYPE_ACCOUNT', '67891567'));
    }

    /**
     * Поиск счёта по id
     *
     * @param integer $id - id аккаунта
     *
     * @return object
     */
    public static function byId($id) {
        return parent::request('FindAccountById', $id);
    }

    /**
     * Поиск списка счетов по заданному фильтру:
     *
     * @param string|null     $alias              - Название счета. Поиск происходит по прямому совпадению.
     *                                              Для задания маски можно указать спец-символы "*" или "?"
     * @param string|null|int $unitId             - Пользователь, которому принадлежат счета
     * @param string|null|int $currency           - Валюта счета
     * @param bool|null       $isDelegatedAccount - Является ли счет делегированным
     *                                              (null - все, false - неделегированные, true - делегированные)
     *
     * @return FindAccountsListRequest
     */
    public static function findList(
        $alias = null, $unitId = null, $currency = null, $isDelegatedAccount = null
    ) {
        $requireArguments = ['alias', 'currency', 'isDelegatedAccount', 'unitId'];
        $data = [
            'alias'              => $alias,
            'currency'           => $currency,
            'unitId'             => $unitId,
            'isDelegatedAccount' => $isDelegatedAccount
        ];
        $request = new FindAccountsListRequest();
        $request = parent::PATR($request, $requireArguments, $data);

        return parent::request('FindAccountsList', $request);
    }

    public static function createBank($unitId = null, $profile = []) {
        $request = parent::PATR(new CreateBankAccountRequest(), ['unitId'], [
            'unitId'    => ($unitId) ? $unitId : env('PROFILE_UNIT_ID', 45316),
            'attribute' => $profile,
        ]);
        return parent::request('CreateBankAccount', $request);
    }


    public static function updateBank($unitId = null, $id = null, $profile = []) {
        $request = parent::PATR(new EditBankAccountRequest(), ['unitId'], [
            'unitId'  => $unitId,
            'id'      => $id,
            'attribute' => $profile,
        ]);
        return parent::request('EditBankAccount', $request);
    }

}