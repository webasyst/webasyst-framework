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
     * @return array
     */
    public function downloadListPoints()
    {
        $data = ['method' => self::METHOD_LIST_POINT, 'prepaid' => 1];

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @return array
     */
    public function downloadPointsForParcels()
    {
        $data = ['method' => self::METHOD_POINTS_FOR_PARCELS,];

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
     * @return array
     */
    public function downloadListZips()
    {
        $data = ['method' => self::METHOD_LIST_ZIPS];

        $result = $this->sendRequest($data);
        return $result;
    }

    /**
     * @return array
     */
    public function downloadListCitiesFull()
    {
        $data = ['method' => self::METHOD_LIST_CITIES_FULL];

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
    public function removeDraft(array $data)
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

        $net = new waNet($options);

        try {
            $result = $net->query($this->url, $data, waNet::METHOD_POST);
        } catch (waException $e) {
            $result = [];
        }

        $method = ifset($data, 'method', false);
        if ($method != self::METHOD_DELIVERY_COSTS) {
            $this->logApiQuery($data, $result);
        }

        // if the error returned, then clear the array
        if (count($result) <= 0 || isset($result[0]['err'])) {
            $result = [];
        }

        return (array)$result;
    }

    /**
     * @param $data
     * @param $result
     */
    protected function logApiQuery($data, $result)
    {
        $string_data = var_export($data, true);
        $errors = 'Successful';

        if (isset($result[0]['err'])) {
            $errors = $result[0]['err'];
        }

        $message = <<<HTML
_________________________________
Request:
{$string_data}

Error:
{$errors}
_________________________________
HTML;

        waLog::log($message, 'wa-plugins/shipping/api_requests.log');
    }
}