<?php
/**
 *
 * @property-read array $rate_zone
 * @property-read string $rate_by
 * @property-read string $currency
 * @property-read array $rate
 * @property-read string $delivery_time
 *
 */
class courierShipping extends waShipping
{
    /**
     * Example of direct usage HTML templates instead waHtmlControl
     * (non-PHPdoc)
     * @see waShipping::getSettingsHTML()
     */
    public function getSettingsHTML($params = array())
    {
        $values = $this->getSettings();
        if (!empty($params['value'])) {
            $values = array_merge($values, $params['value']);
        }

        $view = wa()->getView();

        $cm = new waCountryModel();
        $view->assign('countires', $cm->all());

        if (!empty($values['rate_zone']['country'])) {
            $rm = new waRegionModel();
            $view->assign('regions', $rm->getByCountry($values['rate_zone']['country']));
        }

        if (!empty($values['rate'])) {
            self::sortRates($values['rate']);
            if ($values['rate_by'] == 'price') {
                $values['rate'] = array_reverse($values['rate']);
            }
        } else {
            $values['rate'] = array();
            $values['rate'][] = array(
                'limit' => 0,
                'cost'  => 0.0,
            );
        }

        $app_config = wa()->getConfig();
        if (method_exists($app_config, 'getCurrencies')) {
            $view->assign('currencies', $app_config->getCurrencies());
        }

        $namespace = '';
        if (!empty($params['namespace'])) {
            if (is_array($params['namespace'])) {
                $namespace = array_shift($params['namespace']);
                while (($namspace_chunk = array_shift($params['namespace'])) !== null) {
                    $namespace .= "[{$namspace_chunk}]";
                }
            } else {
                $namespace = $params['namespace'];
            }
        }
        $view->assign('namespace', $namespace);
        $view->assign('values', $values);
        $html = '';
        $html .= $view->fetch($this->path.'/templates/settings.html');
        $html .= parent::getSettingsHTML($params);
        return $html;
    }

    /**
     * Sort rates per orderWeight
     * @param &array $rates
     * @return void
     */
    private static function sortRates(&$rates)
    {
        uasort($rates, create_function('$a,$b', '
		$a=isset($a["limit"])?$a["limit"]:0;
		$b=isset($b["limit"])?$b["limit"]:0;
		return ($a>$b)?1:($a<$b?-1:0);
		'));
    }

    public function calculate()
    {
        $address = $this->verifyAddress();
        if ($address['country'] === false) {
            return _wp('Доставка невозможна в выбранную страну');
        } elseif ($address['region'] === false) {
            return _wp('Доставка невозможна в выбранный регион');
        } else {
            $prices = array();
            $price = null;
            $limit = $this->getPackageProperty($this->rate_by);
            $rates = $this->rate;
            if (!$rates) {
                $rates = array();
            }
            self::sortRates($rates);
            if ($this->rate_by == 'price') {
                $rates = array_reverse($rates);
            }
            foreach ($rates as $rate) {
                $rate = array_map('floatval', $rate);
                switch ($this->rate_by) {
                    case 'price':
                        if (($rate['limit'] < $limit) && (($price === null) || ($price > $rate['cost']))) {
                            $price = $rate['cost'];

                        }
                        break;
                    case 'weight':
                        if (($rate['limit'] < $limit) && (($price === null) || ($price < $rate['cost']))) {
                            $price = $rate['cost'];
                        }
                        break;
                }
                $prices[] = $rate['cost'];
            }
            if ($this->delivery_time) {
                $delivery_date = array_map('strtotime', explode(',', $this->delivery_time, 2));
                foreach ($delivery_date as & $date) {
                    $date = waDateTime::format('humandate', $date);
                }
                unset($date);
                $delivery_date = implode(' —', $delivery_date);
            } else {
                $delivery_date = null;
            }
            return array(
                'delivery' => array(
                    'est_delivery' => $delivery_date,
                    'currency'     => $this->currency,
                    'rate'         => ($limit === null) ? ($prices ? array(min($prices), max($prices)) : null) : $price,
                ),
            );
        }
    }
    private function verifyAddress()
    {
        $address = $this->getAddress();
        $variants = $this->allowedAddress();
        if (empty($address['country'])) {
            $address['country'] = ifempty($variants['country'], false);
        } elseif (!empty($variants['country']) && ($address['country'] != $variants['country'])) {
            $address['country'] = false;
        }

        if (empty($address['region'])) {
            $address['region'] = ifset($variants['region'], false);
        } elseif ($address['region'] != $variants['region']) {
            $address['region'] = false;
        }
        return $address;
    }

    public function allowedAddress($field = null)
    {
        $rate_zone = $this->rate_zone;
        $address = array();
        foreach ($rate_zone as $field => $value) {
            if (!empty($value)) {
                $address[$field] = $value;
            }
        }

        return $address;
    }

    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function allowedWeightUnit()
    {
        return 'kg';
    }

    public function getPrintForms()
    {
        return array();
        return array(
            'delivery_list' => array(
                'name'        => _wp('Лист доставки'),
                'description' => _wp('Лист доставки для курьера'),
            ),
        );
    }

    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        if ($id = 'delivery_list') {
            $view = wa()->getView();
            return $view->fetch($this->path.'/templates/form.html');
        } else {
            throw new waException('print form not found');
        }
    }
}
