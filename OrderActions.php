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
     * @var int|null
     */
    private $user;

    /**
     * @var
     */
    private $basket;

    /**
     * @var
     */
    private $currentBasket;

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

    public function __construct()
    {
        Loader::includeModule("sale");
        Loader::includeModule("catalog");

        $currentBasket = new BasketActions();
        //$basket->getBasketList();
        $this->basket = $currentBasket->getBasket();

        $this->order = Order::create(s1, 1);  //GetAnonymousUserID() - int(3)
    }

    //$props = ["email" => "email@gmail.com"]

    /**
     * @param $userId - передаем id пользователя
     */
    public function createOrder($userId){

        $siteId = Context::getCurrent()->getSite(); //"s1"

        //var_dump($userId);
        //var_dump($this->basket);

        $this->order = Order::create($siteId, $userId);  //GetAnonymousUserID() - int(3)

        $currencyCode = Option::get('sale', 'default_currency', 'UAH'); //"UAH"
        $this->order->setPersonTypeId(1);
        $this->order->setField('CURRENCY', $currencyCode);

        $this->order->setBasket($this->basket);
    }

    public function saveOrder(){
        $this->order->doFinalAction(true);
        $this->order->save();
        $orderId = $this->order->getId();

        return $orderId;
    }

    /**
     * лучше $props!!! - дорлжно для всего подходить
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

        //если пользователь не авторизован, то ищем по email,
        //если не нашли - то GetAnonymousUserID
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
        /*$propertyCollection = $this->order->getPropertyCollection();
        $phoneProp = $propertyCollection->getPhone();
        $phoneProp->setValue($phone);
        $nameProp = $propertyCollection->getPayerName();
        $nameProp->setValue($name);*/

        $orderId = $this->saveOrder();

        return $orderId;
    }


    // Создаём одну отгрузку и устанавливаем способ доставки - "Без доставки" (он служебный)
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

    public function getShipment(){
        return $this->shipment;
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

    public function getPayment(){
        return $this->payment;
    }

    /*
     * array $props = [
     *      "EMAIL" => "",
     *      "PHONE"
     * ]
     */
    public function setProperties(array $props){

    }

    //получить заказ по его id
    public function getOrderById($orderId){
        $order = Order::load($orderId);
        return $order;
    }

/* if($USER->isAuthorized()){
            $USER->GetID();
        }else{

        }
*/

}