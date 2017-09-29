<?php

namespace EasyWay\API;

/**
 * @author avdeev.sa
 *
 */
class EWConnector {
    
    private $url;
    private $user;
    private $pass;
    private $curl;
    
    /**
     * 
     * @param string $url API url
     * @param string $user API user
     * @param string $pass API password
     */
    function __construct($url, $user, $pass) {
     
        $this->url = $url;
        $this->user = $user;
        $this->pass = $pass;
        $this->curl = curl_init();
    }
    
    
    function __destruct() {
        
        curl_close($this->curl);
    }
    
    /**
     * Возвращает предварительный расчет доставки
     * @param string $locationFrom
     * @param string $locationTo
     * @param float $weight
     * @param float $volume
     * @return array(deliveryType, total, estDeliveryTime - array(min, max))
     */
    public function getTariff($locationFrom, $locationTo, $weight, $volume) {
        
        $url = $this->url."getTariff?locationFrom=".urlencode($locationFrom)."&locationTo=".urlencode($locationTo);
        $url .= "&weight=".$weight."&volume=".$volume;
        
        return json_decode($this->getRequest($url), true);
        
    }
    
    /**
     * Возвращает список ПВЗ
     * @param string $order - поле сортировки, по умолчанию "city"
     * @return array(city, address, lat, lng, office, guid, partner, schedule, phone)
     */    
    public function getPickupPoints($order = "city") {
        
        $url = $this->url."getPickupPoints?order=".$order;
        return json_decode($this->getRequest($url), true);
    }
    
    /**
     * Возвращает подробную информацию по заявкам
     * @param array $orderIds
     * @return array(id, 
     *     date, 
     *     regionFrom, 
     *     regionTo,
     *     addressFrom,
     *     addressTo,
     *     weight,
     *     volume,
     *     length,
     *     width,
     *     height,
     *     accessedCost,
     *     cargoCost,
     *     total,
     *     recipient,
     *     recipientPhone,
     *     deliveryCost
     */
    public function getOrderInfo($orderIds) {
        
        $query="";
        
        for ($i = 0; $i < count($orderIds); $i++) {
            if($i == count($orderIds) - 1){
                
                $query = $query.$orderIds[$i];
            } else{
                
                $query = $query.$orderIds[$i].",";
            }
        }
        
        $url = $this->url."getOrderInfo?number=".$query;
        
        $result = $this->getRequest($url);
        
        return json_decode($result, true);
    }
    
    /**
     * Создание заявки
     * @param array $order
     * @return array(isError, errors, data(id, ))
     */
    public function createOrder($order) {
     
        $url = $this->url."createOrder";
        $result =  $this->postRequest($url, json_encode($order));
        
        return json_decode($result, true);
    }
    
    /**
     * Запрос статусов заявок
     * @param array string $orderIds
     * @return array(orderNumber, date, status, arrivalPlanDateTime, dateOrder, sender, receiver, carrierTrackNumber)
     */
    public function getStatus($orderIds){
        
        $query="";
        
        for ($i = 0; $i < count($orderIds); $i++) {
            if($i == count($orderIds) - 1) {
                
                $query = $query.$orderIds[$i];    
            } else{
                
                $query = $query.$orderIds[$i].",";    
            }
        }
        
        $url = $this->url."getStatus?json=&number=".$query;
        
        $result = $this->getRequest($url);
                        
        return json_decode($result, true);
    }
    
    /**
     * Получение макета этикеток для печати
     * @param strin array $orderIds массив номеров заявок
     * @return string html-макет этикеток
     */
    public function getLabel($orderIds) {
    
        $query="";
        
        for ($i = 0; $i < count($orderIds); $i++) {
            if($i == count($orderIds) - 1) {
                
                $query = $query.$orderIds[$i];
            } else{
                
                $query = $query.$orderIds[$i].",";
            }
        }
        
        $url = $this->url."getLabel?number=".$query;
        
        return $this->getRequest($url);
    }
    
    
    protected function getRequest($url) {
                
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_USERPWD, $this->user.":".$this->pass);
        
        $output = curl_exec($this->curl);
        
        return $output;
    }
    
    
    protected function postRequest($url, $data) {
        
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);        
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_USERPWD, $this->user.":".$this->pass);
        
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
            array('Content-Type: application/json; charset=utf-8',
                'Content-Length: '.strlen($data)));
        
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        
        $output = curl_exec($this->curl);
        
        return $output;
    }
}
