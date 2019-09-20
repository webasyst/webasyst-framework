<?php

/**
 * Class boxberryShippingApiManager
 */
class boxberryShippingApiManager
{
    const METHOD_LIST_POINT = 'ListPoints';
    const METHOD_POINTS_FOR_PARCELS = 'PointsForParcels';
    const METHOD_POINTS_DESCRIPTION = 'PointsDescription';
    const METHOD_LIST_ZIPS = 'ListZips';
    const METHOD_LIST_CITIES_FULL = 'ListCitiesFull';
    const METHOD_DELIVERY_COSTS = 'DeliveryCosts';
    const METHOD_CREATE_DRAFT = 'ParselCreate';
    const METHOD_REMOVE_DRAFT = 'ParselDel';
    const LOG_PATH_KEY = 'boxberry_log_path_key';

    /**
     * @var string
     */
    protected $token = '';

    /**
     * @var string
     */
    protected $url = '';

    /**
     * boxberryShippingApiManager constructor.
     * @param string $token
     * @param string $url
     */
    public function __construct($token = '', $url = '')
    {
        $this->token = $token;
        $this->url = $url;
    }

    /**
     * @param $data
     * @return array
     */
    public function downloadListPoints($data)
    {
        $data['method'] = self::METHOD_LIST_POINT;
        $data['prepaid'] = 1;

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    public function downloadPointsForParcels($data)
    {
        $data['method'] = self::METHOD_POINTS_FOR_PARCELS;

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    public function downloadPointDescription($data)
    {
        $data['method'] = self::METHOD_POINTS_DESCRIPTION;

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    public function downloadListZips($data)
    {
        $data['method'] = self::METHOD_LIST_ZIPS;

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    public function downloadListCitiesFull($data)
    {
        $data['method'] = self::METHOD_LIST_CITIES_FULL;

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    public function getDeliveryCosts($data)
    {
        $data['method'] = self::METHOD_DELIVERY_COSTS;
        $data['sucrh'] = '1';
        $data['cms'] = 'Webasyst';

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    public function createDraft($data)
    {
        $data['method'] = self::METHOD_CREATE_DRAFT;
        $data['partner_token'] = 'Webasyst';

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    public function removeDraft($data)
    {
        $data['method'] = self::METHOD_REMOVE_DRAFT;

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    public function sendRequest($data)
    {
        $options = [
            'request_format' => 'default',
            'format'         => waNet::FORMAT_JSON,
            'verify'         => false,
        ];
        $data['token'] = $this->token;

        $log_path = '';
        if (isset($data[self::LOG_PATH_KEY])) {
            $log_path = $data[self::LOG_PATH_KEY];
            unset($data[self::LOG_PATH_KEY]);
        }

        $net = new waNet($options);
        try {
            $result = $net->query($this->url, $data, waNet::METHOD_POST);
        } catch (waException $e) {
            $result = [];
        }

        $this->logApiQuery($data, $result, $log_path);

        // if the error returned, then clear the array
        if (count($result) <= 0 || isset($result[0]['err'])) {
            $result = [];
        }

        return (array)$result;
    }

    /**
     * @param $data
     * @param $result
     * @param string $log_path
     * @return bool
     */
    protected function logApiQuery($data, $result, $log_path = '')
    {
        $method = ifset($data, 'method', false);
        if (!waSystemConfig::isDebug() && $method == self::METHOD_DELIVERY_COSTS) {
            return false;
        }

        $string_data = var_export($data, true);
        $errors = 'Successful';

        if (isset($result[0]['err'])) {
            $errors = $result[0]['err'];
        }

        if (isset($result['err'])) {
            $errors = $result['err'];
        }

        $delivery_costs = '';
        if (waSystemConfig::isDebug() && $method == self::METHOD_DELIVERY_COSTS) {
            $delivery_costs = "\nDelivery costs:\n".var_export($result, true);
        }

        if ($log_path) {
            $log_path = 'wa-cache/apps/'.boxberryShippingHandbookManager::CACHE_PATH.'/cache/'.$log_path.'.php';
        }

        $message = <<<HTML
_________________________________
Request:
{$string_data}

Cache file: 
{$log_path}

Error:
{$errors}
{$delivery_costs}
_________________________________
HTML;

        waLog::log($message, 'wa-plugins/shipping/api_requests.log');

        return true;
    }
}