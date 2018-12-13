<?php

namespace Skalar;

use \Bitrix\Sale\Fuser,
    \Bitrix\Sale\Order,
    \Bitrix\Main\Context,
    \Bitrix\Main\Config\Option,
    \Bitrix\Main\Loader,
    \Bitrix\Sale\Delivery,
    \Bitrix\Sale\Basket,
    \Bitrix\Currency\CurrencyManager,
    \Bitrix\Sale\PaySystem,
    \Bitrix\Main\UserTable,
    \Skalar\BasketActions;

/**
 * Class OrderActions
 * @package Skalar
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
    public $errors = [];

    /**
     * OrderActions constructor.
     */
    public function __construct()
    {
        Loader::includeModule("sale");
        Loader::includeModule("catalog");

        $currentBasket = new BasketActions();
        $this->basket = $currentBasket->getBasket();
        $this->order = $this->createOrder();  //GetAnonymousUserID() - int(3)
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
            print_r($arUser);
            return $arUser["ID"];
        }

    }

    /**
     * @param $email
     * @return int
     */
    public function quickOrder($email){

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

}