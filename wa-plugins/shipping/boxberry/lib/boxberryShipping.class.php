<?php
boxberry_autoload();

/**
 * Class boxberryShipping
 *
 * @property-read string token
 * @property-read string targetstart
 * @property-read string api_url
 * @property-read string service
 * @property-read string notification_name
 * @property-read string courier_title
 * @property-read string declared_price
 * @property-read string free_price
 * @property-read int issuance
 *
 * @property-read int default_weight
 * @property-read string max_weight
 *
 * @property-read int default_width
 * @property-read int default_height
 * @property-read int default_length
 *
 * @property-read int max_width
 * @property-read int max_height
 * @property-read int max_length
 *
 * @property-read string region
 * @property-read string cities
 *
 *
 */
class boxberryShipping extends waShipping
{
    const MAX_DECLARED_PRICE = 300000;

    /**
     * @return array|string
     * @throws waException
     */
    protected function calculate()
    {
        $result = [];

        // check user input
        $errors = (new boxberryShippingCalculateValidate($this))->getErrors();

        // Get pickup points
        if (!$errors && $this->getSettings('point_mode') !== 'off') {
            $points = new boxberryShippingCalculatePoints($this);
            $result += $points->getVariants();
        }

        // Get courier variants
        if (!$errors && $this->getSettings('courier_mode') !== 'off') {
            $points = new boxberryShippingCalculateCourier($this);
            $result += $points->getVariants();
        }

        if (!$errors && !$result) {
            $result = $this->_w('Delivery is not available. Please check the shipping address and the selected payment type.');
        }

        return $result;
    }

    /**
     * Returns information about the pickup point from the order
     *
     * @noinspection PhpUnused
     * @param $order
     * @return array
     * @throws waException
     */
    public function getPointInfo($order)
    {
        $view_helper = new boxberryShippingViewHelper($this);
        return $view_helper->getInfo($order);
    }

    /**
     * Creates a draft in Boxberry's dashboard
     *
     * @param waOrder $order
     * @param array $shipping_data
     * @return array
     */
    public function draftPackage(waOrder $order, $shipping_data = array())
    {
        $class = new boxberryShippingDraftPackage($this, $order, $shipping_data);
        $result = $class->createDraft();

        return $result;
    }

    /**
     * Deletes a draft in Boxberry's dashboard
     *
     * @param waOrder $order
     * @param array $shipping_data
     * @return array
     */
    public function cancelPackage(waOrder $order, $shipping_data = array())
    {
        if (!empty($order->shipping_data['original_track_number'])) {
            $data = [
                'ImId' => $order->shipping_data['original_track_number']
            ];
            $api_manger = new boxberryShippingApiManager($this->token, $this->api_url);
            $api_manger->removeDraft($data);
        }

        return [
            'original_track_number' => null,
            'tracking_number'       => null,
        ];
    }


    /**
     * @return array
     */
    public function allowedAddress()
    {
        return [
            [
                'country' => 'rus',
            ],
        ];
    }

    /**
     * Adds a zip address if courier is enabled
     *
     * @param array $service
     * @return array
     */
    public function requestedAddressFieldsForService($service)
    {
        $fields = [];
        if (isset($service['type'])) {

            if (strpos($service['variant_id'], boxberryShippingCalculateCourier::VARIANT_PREFIX) !== false) {
                $fields = [
                    'zip'    => [
                        'cost'     => true,
                        'required' => true,
                    ],
                    'street' => [
                        'cost'     => true,
                        'required' => true,
                    ],
                ];
            }
        }

        return $fields;
    }

    /**
     * Adds a zip address if courier is enabled
     *
     * @return array
     */
    public function requestedAddressFields()
    {
        $required_fields = ['country', 'region', 'city'];
        $courier_status = $this->getSettings('courier_mode');

        if ($courier_status !== 'off') {
            $required_fields[] = 'zip';
            $required_fields[] = 'street';
        }

        $result = [];
        foreach ($required_fields as $field) {
            $result[$field] = array(
                'cost'     => true,
                'required' => true,
            );
        }

        return $result;
    }

    /**
     * @return string
     */
    public function allowedCurrency()
    {
        return 'RUB';
    }

    /**
     * @return string
     */
    public function allowedWeightUnit()
    {
        return 'g';
    }

    public function allowedLinearUnit()
    {
        return 'm';
    }

    /**
     * @param array $params
     * @return string html
     */
    public function getSettingsHTML($params = array())
    {
        $settings = new boxberryShippingGetSettings($this);

        $html = $settings->getHtml($params);
        $html .= parent::getSettingsHTML($params);

        return $html;
    }

    ######################
    # ADDITIONAL METHODS #
    ######################

    /**
     * Returns the weight of the package.
     * If the goods do not have a weight, takes the default weight.
     * Measured in grams
     *
     * @return float
     */
    public function getParcelWeight()
    {
        $weight = (float)$this->getTotalWeight();

        if (!$weight) {
            $weight = (float)$this->default_weight;
        }
        return $weight;
    }

    /**
     * Checks if a special plugin measures the size of the package
     *
     * @return bool
     */
    public function isPluginDimensions()
    {
        try {
            $status = $this->getAdapter()->getAppProperties('dimensions');
        } catch (Exception $e) {
            $status = false;
        }

        return (bool)$status;
    }

    /**
     * @return string
     */
    public function getPluginPath()
    {
        return $this->path;
    }

    /**
     * @return float
     */
    public function getAssessedPrice()
    {
        $declared_price = $this->declared_price;

        if (!$declared_price) {
            return 0;
        }

        $cost = 0.0;
        //delete whitespaces
        $clear_conditions = preg_replace('@\\s+@', '', $declared_price);

        //divide the expression into parts and save it in an array. Also keep the position of the separator
        $conditions_list = preg_split('@\+|(-)@', $clear_conditions, null, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($conditions_list as $condition) {
            //Delete commas
            $float_value = str_replace(',', '.', trim($condition[0]));

            if (strpos($float_value, '%')) {
                $price = max([$this->getTotalRawPrice(), $this->getTotalPrice()]);

                // recalculate interest to actual values
                $float_value = $price * floatval($float_value) / 100;
                $float_value = round($float_value, 2);
            } else {
                $float_value = floatval($float_value);
            }

            //We perform mathematical operations according to the separator
            if ($condition[1] && (substr($clear_conditions, $condition[1] - 1, 1) == '-')) {
                $cost -= $float_value;
            } else {
                $cost += $float_value;
            }
        }

        // price may not exceed 300,000
        if ($cost > self::MAX_DECLARED_PRICE) {
            $cost = self::MAX_DECLARED_PRICE;
        }

        return round(max(0.0, $cost), 2);
    }

    ###############
    # MAKE PUBLIC #
    ###############

    public function getPackageProperty($property)
    {
        return parent::getPackageProperty($property);
    }

    public function getTotalSize()
    {
        return parent::getTotalSize();
    }

    public function getTotalHeight()
    {
        return parent::getTotalHeight();
    }

    public function getTotalWidth()
    {
        return parent::getTotalWidth();
    }

    public function getTotalLength()
    {
        return parent::getTotalLength();
    }

    public function getTotalWeight()
    {
        return parent::getTotalWeight();
    }

    public function getTotalPrice()
    {
        return parent::getTotalPrice();
    }

    public function getTotalRawPrice()
    {
        return parent::getTotalRawPrice();
    }

    public function getAddress($field = null)
    {
        return parent::getAddress($field);
    }
}

/**
 * Connect classes. Delete when add autoload
 */
function boxberry_autoload()
{
    $autoload = waAutoload::getInstance();

    $autoload->add('boxberryShippingGetSettings', 'wa-plugins/shipping/boxberry/lib/classes/boxberryShippingGetSettings.class.php');
    $autoload->add('boxberryShippingDraftPackage', 'wa-plugins/shipping/boxberry/lib/classes/boxberryShippingDraftPackage.class.php');
    $autoload->add('boxberryShippingViewHelper', 'wa-plugins/shipping/boxberry/lib/classes/boxberryShippingViewHelper.class.php');

    //Handbook
    $autoload->add('boxberryShippingApiManager', 'wa-plugins/shipping/boxberry/lib/classes/boxberryShippingApiManager.class.php');
    $autoload->add('boxberryShippingHandbookManager', 'wa-plugins/shipping/boxberry/lib/classes/handbook/boxberryShippingHandbookManager.class.php');
    $autoload->add('boxberryShippingHandbookPointsForParcels', 'wa-plugins/shipping/boxberry/lib/classes/handbook/boxberryShippingHandbookPointsForParcels.class.php');
    $autoload->add('boxberryShippingHandbookPointDescription', 'wa-plugins/shipping/boxberry/lib/classes/handbook/boxberryShippingHandbookPointDescription.class.php');
    $autoload->add('boxberryShippingHandbookCityZips', 'wa-plugins/shipping/boxberry/lib/classes/handbook/boxberryShippingHandbookCityZips.class.php');
    $autoload->add('boxberryShippingHandbookCityRegions', 'wa-plugins/shipping/boxberry/lib/classes/handbook/boxberryShippingHandbookCityRegions.class.php');
    $autoload->add('boxberryShippingHandbookAvailablePoints', 'wa-plugins/shipping/boxberry/lib/classes/handbook/boxberryShippingHandbookAvailablePoints.class.php');

    //Calculate
    $autoload->add('boxberryShippingCalculate', 'wa-plugins/shipping/boxberry/lib/classes/calculate/boxberryShippingCalculate.class.php');
    $autoload->add('boxberryShippingCalculatePoints', 'wa-plugins/shipping/boxberry/lib/classes/calculate/boxberryShippingCalculatePoints.class.php');
    $autoload->add('boxberryShippingCalculateCourier', 'wa-plugins/shipping/boxberry/lib/classes/calculate/boxberryShippingCalculateCourier.class.php');
    $autoload->add('boxberryShippingCalculateValidate', 'wa-plugins/shipping/boxberry/lib/classes/calculate/boxberryShippingCalculateValidate.class.php');
}