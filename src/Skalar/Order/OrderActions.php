<?php

namespace Skalar\Order;

use \Bitrix\Sale\Order,
    \Bitrix\Main\Context,
    \Bitrix\Main\Loader,
    \Bitrix\Sale\Delivery,
    \Bitrix\Currency\CurrencyManager,
    \Bitrix\Sale\PaySystem,
    \Bitrix\Main\UserTable,
    \Skalar\Basket\BasketActions;

/**
 * Class OrderActions
 * @package Skalar\Basket
 */
class OrderActions
{
    /**
     * @var
     */
    private $basket;

    /**
     * @var \Bitrix\Sale\Order
     */
    private $order;

    /**
     * @var
     */
    private $shipment;

    /**
     * @var
     */
    private $payment;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * OrderActions constructor.
     * @param int $orderId
     */
    public function __construct($orderId = 0)
    {
        Loader::includeModule("sale");
        Loader::includeModule("catalog");

        $currentBasket = new BasketActions();
        $this->basket = $currentBasket->getBasket();
        $this->setOrder($orderId);
    }

    /**
     * @param int $userId
     */
    public function createOrder($userId = 0){

        $userId = $userId ?: \CSaleUser::GetAnonymousUserID();

        $siteId = Context::getCurrent()->getSite();

        $this->order = Order::create($siteId, $userId);

        $currencyCode = CurrencyManager::getBaseCurrency();
        $this->order->setPersonTypeId(1);
        $this->order->setField('CURRENCY', $currencyCode);

        $this->order->setBasket($this->basket);
    }

    /**
     * @return mixed
     */
    public function saveOrder(){
        $this->order->doFinalAction(true);
        $this->order->save();
        $orderId = $this->order->getId();

        return $orderId;
    }

    /**
     * @param string $email
     * @return mixed
     */
    private function getUser($email = ""){

        $result = UserTable::getList(array(
            'select' => array('ID','EMAIL'),
            'filter' => array('EMAIL'=> $email),
            'order' => array('ID'=>'ASC'),
            'limit' => 1
        ));
        while ($arUser = $result->fetch()) {
            return $arUser["ID"];
        }

    }

    /**
     * @param $email
     * @return int
     */
    public function quickOrder($email){

        if (count($this->basket) == 0) {
            $this->setErrors("No products in the cart");
            return false;
        }

        global $USER;

        $userIdByEmail = $this->getUser($email);

        if($USER->isAuthorized()) {
            $userId = $USER->GetID();
        }
        elseif(empty($userIdByEmail)){
            $userId = $userIdByEmail;
        }else{
            $userId = \CSaleUser::GetAnonymousUserID();
        }

        $this->createOrder($userId);

        $this->setShipment();

        $this->setPayment();

        // Устанавливаем свойства
        $propertyCollection = $this->order->getPropertyCollection();
        $phoneProp = $propertyCollection->getUserEmail();
        $phoneProp->setValue($email);

        $orderId = $this->saveOrder();

        return $orderId;
    }

    // Создаём одну отгрузку и устанавливаем способ доставки - "Без доставки" (он служебный)

    /**
     * @param null $serviceId
     */
    public function setShipment($serviceId = null){

        $shipmentCollection = $this->order->getShipmentCollection();

        $this->shipment = $shipmentCollection->createItem();

        if(empty($serviceId)){
            $serviceId = Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
        }
        $service = Delivery\Services\Manager::getById($serviceId);

        $this->shipment->setFields(array(
            'DELIVERY_ID' => $service['ID'],
            'DELIVERY_NAME' => $service['NAME'],
        ));

        $basketItems = $this->basket->getBasketItems();
        foreach ($basketItems as $item){
            $shipmentItemCollection = $this->shipment->getShipmentItemCollection();
            $shipmentItem = $shipmentItemCollection->createItem($item);
            $shipmentItem->setQuantity($item->getQuantity());
        }

    }

    /**
     * @param $id
     */
    public function setPayment($id = 1){
        $paymentCollection = $this->order->getPaymentCollection();
        $this->payment = $paymentCollection->createItem();
        $paySystemService = PaySystem\Manager::getObjectById($id);
        $this->payment->setFields(array(
            'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
            'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
        ));
    }

    /**
     * @param array $props
     */
    public function setProperties(array $props){
        $propertyCollection = $this->order->getPropertyCollection();
        foreach ($props as $id => $value) {
            $somePropValue = $propertyCollection->getItemByOrderPropertyId($id);
            $somePropValue->setValue($value);
            $somePropValue->save();
        }
    }

    //получить заказ по его id

    /**
     * @param $orderId
     * @return mixed
     */
    public function getOrderById($orderId){
        $order = Order::load($orderId);
        return $order;
    }

    /**
     * @param $orderId
     */
    public function deleteOrder($orderId)
    {
        $result = Order::delete($orderId);
        if (!$result->isSuccess()) {
            $this->setErrors($result->getErrorMessages());
        }
    }

    /**
     * @param int $orderId
     */
    public function setOrder($orderId = 0)
    {
        $orderId = intval($orderId);
        $this->order = !empty($orderId) ? Order::load($orderId) : $this->createOrder();
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