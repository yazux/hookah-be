<?php

namespace App\Classes;

use App\Exceptions\CustomException;
use Moneta\Types\CancelTransactionRequest;
use Moneta\Types\ConfirmTransactionRequest;
use Moneta\Types\InvoiceRequest;
use Moneta\Types\RefundRequest;


/**
 * Класс для работы с оплатой в сервисе Moneta.ru
 * @package App\Classes
 */
class MonetaPayment extends MonetaBase implements MonetaInterface
{
    /**
     * Выставление счёта пользователю
     *
     * @param null   $payee - счёт магазина
     * @param null   $amount - цена (в рублях)
     * @param null   $clientTransaction - id операции внетри системы
     *
     * @return mixed
     */
    public static function invoice(
        $payee = null, $amount = null, $clientTransaction = null
    ) {

        $xml = '<ns1:attribute><ns1:key>AUTHORIZEONLY</ns1:key><ns1:value>1</ns1:value></ns1:attribute>';
        $xml = new \SoapVar($xml, XSD_ANYXML);
        $operationInfo = new \SoapVar(array($xml), SOAP_ENC_OBJECT);

        $request = parent::PATR(new InvoiceRequest(), ['payee', 'amount', 'clientTransaction', 'operationInfo'], [
            'payee'  => $payee,
            'amount' => $amount,
            'clientTransaction' => $clientTransaction,
            //'operationInfo'     => ["AUTHORIZEONLY" => '1']
            'operationInfo' => $operationInfo
        ]);

        return parent::request('Invoice', $request);
    }

    public static function getTransactionById($id)
    {
        return parent::request('GetOperationDetailsById', $id);
    }

    public static function confirmTransaction($id)
    {
        $request = parent::PATR(new ConfirmTransactionRequest(), ['transactionId'], [
            'transactionId'  => $id,
        ]);
        return parent::request('ConfirmTransaction', $request);
    }

    public static function cancelTransaction($id)
    {
        $request = parent::PATR(new CancelTransactionRequest(), ['transactionId'], [
            'transactionId'  => $id,
        ]);
        return parent::request('CancelTransaction', $request);
    }

    public static function refundTransaction($id)
    {
        $request = parent::PATR(new RefundRequest(), ['transactionId'], [
            'transactionId'  => $id,
        ]);
        return parent::request('Refund', $request);
    }

}
