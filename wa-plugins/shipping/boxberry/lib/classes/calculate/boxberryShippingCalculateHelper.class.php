<?php

/**
 * Class boxberryShippingCalculateHelper
 */
class boxberryShippingCalculateHelper
{
    /**
     * To make FROM prices in the new checkout
     */
    const MAGIC_NUMBER_TO_MAKE_RANGE = 1;

    /**
     * @var boxberryShipping|null
     */
    protected $bxb = null;

    /**
     * boxberryShippingCalculateHelper constructor.
     * @param boxberryShipping $bxb
     */
    public function __construct(boxberryShipping $bxb)
    {
        $this->bxb = $bxb;
    }

    /**
     * @param $data
     * @return array
     */
    public function getDeliveryCostsAPI($data)
    {
        $data['paysum'] = $this->getPaysum();
        $data['targetstart'] = $this->bxb->targetstart;
        $data['weight'] = $this->bxb->getParcelWeight();
        $data['ordersum'] = $this->getOrderSum($data['paysum']);

        $data = array_merge($data, $this->getDimensions());

        $api_manager = $this->getApiManager();
        $rate = $api_manager->getDeliveryCosts($data);

        //If the price is less than 10 rubles, then something went wrong.
        if (empty($rate) || (isset($rate['price']) && $rate['price'] < 10)) {
            $rate['price'] = false;
        }

        if ($rate['price']) {
            $free_price = (float)$this->bxb->free_price;

            // Check if you need to make delivery free
            if ($free_price > 0 && $this->bxb->getTotalPrice() > $free_price) {
                $rate['price'] = 0;
            }
        }

        $result = [
            'price'           => $rate['price'],
            'delivery_period' => ifset($rate, 'delivery_period', 0)
        ];

        return $result;
    }


    /**
     * @param float|int $paysum
     * @return float|int
     */
    public function getOrderSum($paysum = 0)
    {
        // Если у покупателя нужно принять оплату, то объявленная стоимость всегда должна равняться стоимости заказа
        if ($paysum > 0) {
            $result = $paysum;
        } else {
            $result = $this->getAssessedPrice();
        }

        return $result;
    }

    /**
     * @return float
     */
    public function getAssessedPrice()
    {
        $declared_price = $this->bxb->declared_price;

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
                $price = $this->bxb->getTotalPrice();

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
        if ($cost > boxberryShipping::MAX_DECLARED_PRICE) {
            $cost = boxberryShipping::MAX_DECLARED_PRICE;
        }

        return round(max(0.0, $cost), 2);
    }

    /**
     * get package sizes
     *
     * @return array
     */
    protected function getDimensions()
    {
        $plugin_sizes = $this->bxb->getTotalSize();
        $result = [];

        $height = ifset($plugin_sizes, 'height', 0);
        $width = ifset($plugin_sizes, 'width', 0);
        $length = ifset($plugin_sizes, 'length', 0);

        // If some size is not valid, then we take the standard sizes
        if (empty($length) || empty($width) || empty($height)) {
            $height = $this->bxb->default_height;
            $width = $this->bxb->default_width;
            $length = $this->bxb->default_length;
        }

        // convert to cm
        // Centimeters are the requirements of boxberry
        $result['height'] = (float)$length * 100;
        $result['width'] = (float)$width * 100;
        $result['depth'] = (float)$height * 100;

        return $result;
    }

    /**
     * Returns information about whether a specific point was selected.
     * @return bool
     */
    protected function isVariantSelected()
    {
        $id = $this->bxb->getSelectedServiceId();

        $result = false;
        if ($id && strpos($id, $this->getPrefix()) !== false) {
            $result = true;
        }

        return $result;
    }

    /**
     * Returns the payment method for the plugin
     *
     * @return array
     */
    protected function getPayment()
    {
        $result = [];
        $mode = $this->getMode();

        if ($mode === 'all') {
            $result = [
                waShipping::PAYMENT_TYPE_CARD    => true,
                waShipping::PAYMENT_TYPE_CASH    => true,
                waShipping::PAYMENT_TYPE_PREPAID => true,
            ];
        } elseif ($mode === 'prepayment') {
            $result = [
                waShipping::PAYMENT_TYPE_PREPAID => true,
            ];
        }

        return $result;
    }

    /**
     * Returns the amount of cash on delivery if the payment option is not an advance payment
     * If the payment option is not selected, we always consider the minimum cost
     *
     * @return float
     */
    protected function getPaysum()
    {
        $result = 0.0;

        $payment_type = $this->bxb->getSelectedPaymentTypes();
        // Если плагин оплаты позволят рассчитаться и авансом, и на месте, то значит что-то пошло не так.
        // В таком случае считаем минимальную цену.
        if ($payment_type && !in_array(waShipping::PAYMENT_TYPE_PREPAID, $payment_type)) {
            $result = $this->bxb->getTotalPrice();
        }

        return $result;
    }

    /**
     * For prepayment, you can only pay with remote payments.
     *
     * @return bool
     */
    protected function getErrors()
    {
        $payment_type = $this->bxb->getSelectedPaymentTypes();
        $not_only_prepayment = count($payment_type) > 1 || !in_array(waShipping::PAYMENT_TYPE_PREPAID, $payment_type);

        $result = false;
        if ($this->getMode() === 'prepayment' && $payment_type && $not_only_prepayment) {
            $result = true;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return 'Europe/Moscow';
    }

    /**
     * @return boxberryShippingApiManager
     */
    protected function getApiManager()
    {
        return new boxberryShippingApiManager($this->bxb->token, $this->bxb->api_url, $this->bxb);
    }

    /**
     * @return string
     */
    public static function getVariantSeparator()
    {
        return '__';
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return '';
    }

    public static function findCityName($query_city, $city_names)
    {
        foreach ($city_names as $city_name) {
            $original_city_name = $city_name;

            $city_name = trim(mb_strtolower($city_name));
            $city_name = preg_replace("/[её]/u", "е", $city_name);
            $city_name = preg_replace("/[\-\s+]/", " ", $city_name);

            $query_city = mb_strtolower($query_city);
            $query_city = preg_replace("/[её]/u", "е", $query_city);
            $query_city = preg_replace("/[\-\s+]/", " ", $query_city);

            if ($query_city === $city_name) {
                return $original_city_name;
            }
        }

        return null;
    }

}
