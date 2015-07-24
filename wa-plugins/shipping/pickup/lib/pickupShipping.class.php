<?php

/**
 *
 * @property-read array $rate_zone
 * @property-read string $rate_by
 * @property-read string $currency
 * @property-read array $rate
 * @property-read string $delivery_time
 * @property-read string $prompt_address
 */
class pickupShipping extends waShipping
{
    /**
     * Example of direct usage HTML templates instead waHtmlControl
     * (non-PHPdoc)
     * @see waShipping::getSettingsHTML()
     * @param array $params
     * @return string HTML
     */
    public function getSettingsHTML($params = array())
    {
        $values = $this->getSettings();
        if (!empty($params['value'])) {
            $values = array_merge($values, $params['value']);
        }

        $view = wa()->getView();

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
        $view->assign('p', $this);

        $html = '';
        $html .= $view->fetch($this->path.'/templates/settings.html');
        $html .= parent::getSettingsHTML($params);

        return $html;
    }

    protected function calculate()
    {
        $currency = $this->currency;
        $rates = $this->rate;

        $deliveries = array();
        $i = 1;    // start from index 1
        foreach ($rates as $rate) {
            $deliveries[$i++] = array(
                'name'         => $rate['location'],
                'currency'     => $currency,
                'rate'         => $rate['cost'],
                'est_delivery' => ''
            );
        }

        return $deliveries;
    }

    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function allowedWeightUnit()
    {
        return 'kg';
    }

    public function requestedAddressFields()
    {
        return $this->prompt_address ? array() : false;
    }
}
