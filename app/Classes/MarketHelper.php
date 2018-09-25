<?php

namespace App\Classes;

use App\Interfaces\ModuleModelInterface;
use App\Modules\Market\Controllers\CartController;
use App\Modules\Market\Controllers\SKUController;
use App\Modules\Market\Model\Cart;
use App\Modules\Market\Model\Discount;
use App\Modules\Market\Model\ProductInCart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\CustomValidationException;
use Illuminate\Http\Request;
use App\Exceptions\CustomException;


class MarketHelper
{
    public static function calcProductData(
        $sku_id, $cart_id, $amount
    ) {
        $data = [
            'sku_id'  => $sku_id,
            'cart_id' => $cart_id,
            'amount'  => $amount
        ];

        if (!isset($data['sku_id'])
            || !isset($data['cart_id'])
            || !isset($data['amount'])
        ) {
            throw new CustomException(
                $data, [], 400,
                'Не указан один из обязательных параметов'
            );
        }

        $SKUC = new SKUController();
        $SKU = $SKUC->getSKUById($data['sku_id'], false);

        if (!$SKU || !isset($SKU['product'])) {
            throw new CustomException(
                $data, [], 500,
                'Не найдены параметры товара требуемые для заказа.' .
                ' Пожалуйста, обратитесь в тех поддержку.'
            );
        }

        //суммарная скидка на товар
        $discount = self::getProductDiscount($SKU['product']['discounts']);
        //все цены со скидками
        $price = self::calcSKUPrice($SKU['price'], $amount, $discount);

        $data = [
            'sku_id'              => (int)  $data['sku_id'],                // id Торгового Предложения (комплектации)
            'cart_id'             => (int)  $data['cart_id'],               // id корзины, куда добавляется товар
            'amount'              => (int)  $data['amount'],                // количество единиц товара
            'product_id'          => (int)  $SKU['product']['id'],          // id товара из каталога
            'price'               => (float) $SKU['price'],                 // цена единицы товара
            'discount'            => (float) $price['discount'],            // скидка на единицу товара (в рублях)
            'discount_price'      => (float) $price['discount_price'],      // цена с учётом скидки на единицу товара
            'full_price'          => (float) $price['full_price'],          // цена с учётом количества товара
            'full_discount_price' => (float) $price['full_discount_price'], // цена с учётом количества товара и скидки
            'full_discount'       => (float) $price['full_discount'],       // размер скидки на все товары в рублях
        ];

        return $data;
    }

    /**
     * Считает суммарную скидку исходя из списка скидок
     *
     * @param array $discounts - Массив скидок (экземпляров Discount)
     *
     * @return array
     */
    public static function getProductDiscount($discounts)
    {
        $result = [
            'value'   => 0, //фактический размер скидки (на пример 100 рублей)
            'percent' => 0  //процентный размер скидки (на пример 15%)
        ];

        if (!count($discounts)) return $result;

        foreach ($discounts as $discount) {
            if ((int) $discount['active'] === 1 && (int) $discount['type'] === 1) {
                $result['value'] = (float) $result['value'] + (float) $discount['value'];
            } else {
                $result['percent'] = (float) $result['percent'] + (float) $discount['value'];
            }
        }

        return $result;
    }

    /**
     * @param float   $price    - Цена за единицу товара
     * @param integer $amount   - Количество единиц товара
     * @param array   $discount - Массив с посчитанными скидками в формате:
     * [
     *  'value'   => 0, //фактический размер скидки (на пример 100 рублей)
     *  'percent' => 0  //процентный размер скидки (на пример 15%)
     * ]
     * Такой массив возвращает функция MarketHelper::getProductDiscount()
     *
     * @return array
     */
    public static function calcSKUPrice(
        $price = 0.0, $amount = 0,
        $discount = ['value' => 0, 'percent' => 0]
    ) {
        $result = [
            'discount'            => 0.0, // Размер скидки за единицу товара (в рублях)
            'discount_price'      => 0.0, // цена с учётом скидки за единицу товара
            'full_price'          => 0.0, // цена с учётом количества товара, но без скидки
            'full_discount_price' => 0.0, // цена с учётом количества товара и скидки
            'full_discount'       => 0.0, // размер скидки на все товары в рублях
        ];

        $result['discount'] =
            (float) $discount['value'] +
            (float) (
                ( (float) $discount['percent'] * (float) $price ) / 100
            ); //считаем процентный размер скидки в рублях
        $result['full_price']          = (float) $price * (int) $amount;
        $result['full_discount']       = (float) $result['discount'] * (int) $amount;
        $result['discount_price']      = (float) $price - (float) $result['discount'];
        $result['full_discount_price'] = (float) $result['full_price'] - (float) $result['full_discount'];

        return $result;
    }

    public static function calcCart($cart_id) {
        $Products = ProductInCart::where('cart_id', $cart_id)->get();
        $CartC  = new CartController();
        $Cart   = $CartC->getCartById($cart_id, false);
        $Cart->amount         = 0;
        $Cart->price          = 0.0;
        $Cart->discount       = 0.0;
        $Cart->discount_price = 0.0;

        foreach ($Products as $Product) {
            $Cart->amount         += (int)   $Product['amount'];
            $Cart->price          += (float) $Product['full_price'];
            $Cart->discount       += (float) $Product['full_discount'];
            $Cart->discount_price += (float) $Product['full_discount_price'];
        }
        $Cart->save();
        return $Cart;
    }
}
