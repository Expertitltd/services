<?php

namespace Skalar\Basket;

use \Bitrix\Sale\Fuser,
    \Bitrix\Sale\Basket,
    \Bitrix\Sale\Order,
    \Bitrix\Main\Context,
    \Bitrix\Currency\CurrencyManager,
    \Bitrix\Main\Loader;

/**
 * Class BasketActions
 * @package Skalar\Basket
 */
class BasketActions
{
    /**
     * @var int|null
     */
    private $user;

    /**
     * @var \Bitrix\Sale\BasketBase
     */
    private $basket;

    /**
     * @var array
     */
    public $errors = [];

    /**
     * BasketActions constructor.
     * получаем корзину для текущего юзера
     */
    public function __construct()
    {
        Loader::includeModule("sale");
        $this->user = Fuser::getId();

        $this->basket = Basket::loadItemsForFUser(
            $this->user,
            Context::getCurrent()->getSite()
        );
        $this->basket->save();
    }

    /**
     * @param $moduleId
     * @param $productId
     * @return $basketItem || null
     */
    private function getExistsItem($moduleId, $productId)
    {
        $basketCollection = $this->basket->getBasketItems();
        foreach ($basketCollection as $basketItem) {
            if ($basketItem->getField('PRODUCT_ID') == $productId && $basketItem->getField('MODULE') == $moduleId) {
                return $basketItem;
            }
        }
        return null;
    }

    /**
     * @param $productId
     * @param int $quantity
     * @param array $props = [
     *                        ['NAME' => 'Test prop', 'CODE' => 'TEST_PROP', 'VALUE' => 'test value', 'SORT' => 500],
     *                      ],
     * @param $customPrice (разделитель - точка)
     * @return \Bitrix\Main\Result|\Bitrix\Sale\Result
     */
    public function addToBasket($productId, $quantity = 1, array $props = [], $customPrice = null)
    {
        $fields = array();
        if($customPrice !== null)
        {
            $fields['PRICE'] = $customPrice;
            $fields['CUSTOM_PRICE'] = 'Y';
        }else{
            $fields['PRODUCT_PROVIDER_CLASS'] = 'CCatalogProductProvider';
        }

        if ($item = $this->getExistsItem('catalog', $productId)) {
            $fields['QUANTITY'] = $item->getQuantity() + $quantity;
        }
        else {
            $item = $this->basket->createItem('catalog', $productId);

            $fields["QUANTITY"] = $quantity;
            $fields["CURRENCY"] = CurrencyManager::getBaseCurrency();
            $fields["LID"] = Context::getCurrent()->getSite();

        }

        $item->setFields($fields);
        $result = $this->basket->save();

        if(!empty($props)){
            $basketPropertyCollection = $item->getPropertyCollection();

            $basketPropertyCollection->setProperty($props);
            $basketPropertyCollection->save();
        }

        if (!$result->isSuccess()) {
            $this->setErrors($result->getErrorMessages());
            return false;
        }
        return true;
    }

    /**
     * @param array $products = [
     *                              [ 'id' => 'quantity', 6 => '10', '25' => '2',  ...],
     *                         ],
     */
    public function addProductsWithQuantity(array $products)
    {
        foreach ($products as $key => $product){
            $productId = $key;
            $quantity = $product;

            $this->addToBasket($productId, $quantity);
        }
    }

    /**
     * @param array $ids
     */
    public function addToBasketByIds(array $ids)
    {
        $products = array_fill_keys( $ids, 1);
        $this->addProductsWithQuantity($products);
    }

    /**
     * @param array $products = [
     *                              id => [ 'quantity' => value,
     *                                      'props' => [
                                 *                        ['NAME' => 'Test prop', 'CODE' => 'TEST_PROP', 'VALUE' => 'test value', 'SORT' => 500],
                                 *                      ],
     *                                      'customPrice' => "value"],
     *                         ],
     *
     */
    public function addProductAdvanced(array $products){
        foreach ($products as $key => $product){
            $productId = $key;

            $quantity = $product["quantity"];

            if(!empty($product["props"])){
                $props = $product["props"];
            }else{
                $props = [];
            }

            if(!empty($product["customPrice"])){
                $customPrice = $product["customPrice"];
            }else{
                $customPrice = null;
            }

            $this->addToBasket($productId, $quantity, $props, $customPrice);
        }
    }

    /**
     * @return \Bitrix\Sale\BasketBase
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @param \Bitrix\Sale\BasketBase $basket
     */
    public function setBasket($basket)
    {
        $this->basket = $basket;
    }

    /**
     * @param $orderId
     */
    public function setBasketByOrderId($orderId)
    {
        $this->basket = Order::load($orderId)->getBasket();
    }

    /**
     * @param $order
     */
    public function setBasketByOrder($order)
    {
        $this->basket = Basket::loadItemsForOrder($order);;
    }

    /**
     * @return array
     */
    public function getBasketList(){
        $basketList = [];

        $basketItems = $this->basket->getBasketItems();

        foreach ($basketItems as $item){
            $basketList[$item->getId()] =
            [
                "id" => $item->getId(),
                "product_id" => $item->getProductId(),
                "name" => $item->getField('NAME'),
                "fuser_id" => $item->getField('FUSER_ID'),
                "order_id" => $item->getField('ORDER_ID'),
                "price" => $item->getPrice(),
                "custom_price" => $item->getField('CUSTOM_PRICE'),
                "base_price" => $item->getField('BASE_PRICE'),
                "currency" => $item->getField('BASE_PRICE'),
                "quantity" => $item->getQuantity('CURRENCY'),
                "final_price" => $item->getFinalPrice(),
                "weight" => $item->getWeight(),
                "can_buy" => $item->canBuy(),
                "is_delay" => $item->isDelay(),
                "date_insert" => $item->getField('DATE_INSERT'),
                "date_update" => $item->getField('DATE_UPDATE'),
            ];

        }

        return $basketList;
    }

    /**
     * @param $productId
     * @return \Bitrix\Sale\Result|bool
     * по id товара (текущего пользователя) ищем id записи в корзине (таблица b_sale_basket)
     * и удаляем запись из корзины
     */
    public function deleteFromBasket($productId){
        $item = $this->basket->getExistsItem('catalog', $productId);
        $this->basket->getItemById($item);
        if($item !== null){
            $item->delete();
            return $this->basket->save();
        }
        return false;
    }

    /**
     * @param array $productIds
     * @return \Bitrix\Sale\Result|bool
     * Массовое удаление товаров из корзины по product Ids
     */
    public function deleteProducts(array $productIds){
        foreach ($productIds as $productId){
            $this->deleteFromBasket($productId);
        }
    }

    /**
     * @param array $products = [
     *                              id => [ 'quantity' => value,
     *                                      'props' => [
     *                                           ['NAME' => 'Test prop', 'CODE' => 'TEST_PROP', 'VALUE' => 'test value', 'SORT' => 500],
     *                                      ],
     *                                      'customPrice' => "value"],
     *                         ],
     * Массовое изменение товаров из корзины - повторная установка
     * Полностью всё удаляем из корзины и добавляем товары из пришедшего массива
     */
    public function resetProducts(){
        $basketItems = $this->basket->getBasketItems();
        foreach ($basketItems as $item){
            $item->delete();
            $this->basket->save();
        }
    }

    /**
     * @param array $products = [
     *                              [ 'id' => 'quantity', 6 => '10', '25' => '2',  ...],
     *                         ],
     */
    public function updateProductsWithQuantity(array $products)
    {
        $this->resetProducts();
        $this->addProductsWithQuantity($products);
    }

    /**
     * @param array $ids
     */
    public function updateByIds(array $ids)
    {
        $products = array_fill_keys( $ids, 1);
        $this->updateProductsWithQuantity($products);
    }

    /**
     * @param array $products = [
     *                              id => [ 'quantity' => value,
     *                                      'props' => [
     *                                           ['NAME' => 'Test prop', 'CODE' => 'TEST_PROP', 'VALUE' => 'test value', 'SORT' => 500],
     *                                      ],
     *                                      'customPrice' => "value"],
     *                         ],
     *
     */
    public function updateProductAdvanced(array $products){
        $this->resetProducts();
        $this->addProductAdvanced($products);
    }

    /**
     * @param $value
     */
    public function setErrors($value)
    {
        if(is_array($value)){
            $this->errors = array_merge($this->errors, $value);
        }else if(is_string($value)){
            $this->errors[] = $value;
        }
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}