<?php
class checkBill
{
    /**
     *
     * логин (идентификатор) магазина;
     * @var string
     */
    public $login;
    /**
     *
     * пароль для магазина
     * @var string
     */
    public $password;
    /**
     *
     * уникальный идентификатор счета (максимальная длина 30 байт)
     * @var string
     */
    public $txn;
}

class checkBillResponse
{
    /**
     *
     * идентификатор пользователя (номер телефона)
     * @var string
     */
    public $user;
    /**
     *
     * сумма, на которую выставлен счет (разделитель «.»)
     * @var string
     */
    public $amount;
    /**
     *
     * дата выставления счета (в формате dd.MM.yyyy HH:mm:ss)
     * @var string
     */
    public $date;
    /**
     *
     * время действия счета (в формате dd.MM.yyyy HH:mm:ss)
     * @var string
     */
    public $lifetime;
    /**
     *
     * статус счета (см. Справочник статусов счетов)
     * @var int
     */
    public $status;
}

class getBillList
{
    public $login; // string
    public $password; // string
    public $dateFrom; // string
    public $dateTo; // string
    public $status; // int
}

class getBillListResponse
{
    public $txns; // string
    public $count; // int
}

class cancelBill
{
    /**
     *
     * логин (id) магазина
     * @var string
     */
    public $login;
    /**
     *
     * пароль для магазина
     * @var string
     */
    public $password;
    /**
     *
     * уникальный идентификатор счета (максимальная длина 30 байт);
     * @var string
     */
    public $txn;
}

class cancelBillResponse
{
    public $cancelBillResult; // int
}

class createBill
{
    /**
     *
     * логин (id) магазина
     * @var string
     */
    public $login;
    /**
     *
     * пароль для магазина
     * @var string
     */
    public $password;
    /**
     *
     * идентификатор пользователя (номер телефона)
     * @var string
     */
    public $user;
    /**
     *
     * сумма, на которую выставляется счет (разделитель «.»)
     * @var string
     */
    public $amount;
    /**
     *
     * комментарий к счету, который увидит пользователь (максимальная длина 255 байт)
     * @var string
     */
    public $comment;
    /**
     *
     * уникальный идентификатор счета (максимальная длина 30 байт);
     * @var string
     */
    public $txn;
    /**
     *
     * время действия счета (в формате dd.MM.yyyy HH:mm:ss);
     * @var string
     */
    public $lifetime;
    /**
     *
     * отправить оповещение пользователю (1 - уведомление SMS-сообщением, 2 - уведомление звонком, 0 - не оповещать);
     * @var int
     */
    public $alarm; // int
    /**
     *
     * флаг для создания нового пользователя (если он не зарегистрирован в системе).
     * В ответ возвращается результат выполнения функции (см. Справочник кодов завершения).
     * @var boolean
     */
    public $create; // boolean
}

class createBillResponse
{
    public $createBillResult; // int
}

/**
 * IShopServerWSService class
 */
class IShopServerWSService extends nusoap_client
{
    private static $classmap = array(
        'checkBill'           => 'checkBill',
        'checkBillResponse'   => 'checkBillResponse',
        'getBillList'         => 'getBillList',
        'getBillListResponse' => 'getBillListResponse',
        'cancelBill'          => 'cancelBill',
        'cancelBillResponse'  => 'cancelBillResponse',
        'createBill'          => 'createBill',
        'createBillResponse'  => 'createBillResponse',
    );

    public function IShopServerWSService($wsdl = "IShopServerWS.wsdl", $options = array())
    {
        foreach (self::$classmap as $key => $value) {
            if (!isset($options['classmap'][$key])) {
                $options['classmap'][$key] = $value;
            }
        }
        parent::__construct($wsdl, $options);
    }

    public function getDebug()
    {
        $result = null;
        $class = get_parent_class($this);
        switch ($class) {
            case 'SoapClient':
                {
                    $result = var_export(array(
                        $this->__getLastRequestHeaders(),
                        $this->__getLastRequest(),
                        $this->__getLastResponse(),
                        $this->__getLastResponseHeaders(),
                    ));
                    break;
                }
            case 'nusoap_client':
                {
                    $result = parent::getDebug();
                    break;
                }
            default:
                {
                    $result = __METHOD__;
                    break;
                }
        }
        return $result;
    }

    /**
     *
     *
     * @param checkBill $parameters
     * @return checkBillResponse
     */
    public function checkBill(checkBill $parameters)
    {
        return $this->castCall(__FUNCTION__, array($parameters));
    }

    /**
     *
     *
     * @param getBillList $parameters
     * @return getBillListResponse
     */
    public function getBillList(getBillList $parameters)
    {
        return $this->castCall(__FUNCTION__, array($parameters));
    }

    /**
     *
     *
     * @param cancelBill $parameters
     * @return cancelBillResponse
     */
    public function cancelBill(cancelBill $parameters)
    {
        return $this->castCall(__FUNCTION__, array($parameters));
    }

    /**
     *
     *
     * @param createBill $parameters
     * @return createBillResponse
     */
    public function createBill(createBill $parameters)
    {
        return $this->castCall(__FUNCTION__, array($parameters));
    }

    private function castCall($method, $parameters)
    {
        $call_result = $this->call($method, $parameters);
        $class = $method.'Response';
        $result = new $class();
        $vars = get_class_vars($class);
        foreach ($vars as $key => $var) {
            if (isset($call_result[$key])) {
                $result->$key = $call_result[$key];
            }
        }
        return $result;
    }

}
