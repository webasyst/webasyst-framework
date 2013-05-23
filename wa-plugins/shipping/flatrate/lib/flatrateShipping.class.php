<?php

/**
 *
 * @property-read float $cost
 * @property-read string $currency
 * @property-read string $delivery
 * @property-read string $promt_address
 *
 */
class flatrateShipping extends waShipping
{
    /**
     * Core shipping rate calculation method.
     * Returns the list (array) of estimated shipping rates and transit times
     *
     * Useful parent class (waShipping) methods to be used in calculate():
     * $price = $this->getTotalPrice();
     * $weight = $this->getTotalWeight();
     */
    public function calculate()
    {
        /*
         Use following methods to obtain package information:

         — $this->getTotalWeight(); // returns overall package weight
         — $this->getTotalPrice(); // returns declared shipment value
         — $this->getRecipientName(); // returns full name of the package recipient
         — $this->getAddress($field = null); // returns either entire address (array) or exact address field, e.g. 'country', 'region', 'city', 'zip', 'street' or any custom defined field

         Use $this->VAR_ID to access module settings as defined in the config/settings.php
         */

        return array(
            'ground' => array(
                'name'         => $this->_w('Ground shipping'),
                'description'  => '',
                'est_delivery' => waDateTime::format('humandate', strtotime($this->delivery)), //string
                'currency'     => $this->currency,
                'rate'         => $this->cost,
            ),
            /*
             //ADD AS MANY SHIPPING OPTIONS AS YOU LIKE
             'priority' => array(
             'name' => 'Priority shipping',
             'description' => '',
             'estimated_delivery_date' => strtotime($this->delivery),
             'currency' => $this->currency,
             'rate' => $this->cost,
             ),
             'expedited' => array(
             'name' => 'Expedited shipping',
             'description' => '',
             'estimated_delivery_date' => strtotime($this->delivery),
             'currency' => $this->currency,
             'rate' => $this->cost,
             ),
             */
        );
    }

    /**
     * Returns ISO3 code of the currency this module can work with (or array of ISO3 codes)
     * @see waShipping::allowedCurrency()
     */
    public function allowedCurrency()
    {
        return $this->currency; // return array('USD','EUR');

    }

    /**
     * Returns the weight unit this module work with (or array of weight units)
     * @see waShipping::allowedWeightUnit()
     */
    public function allowedWeightUnit()
    {
        return 'kg'; // return array('kg','lbs');

    }

    /**
     * Returns the general tracking information (HTML)
     * @see waShipping::tracking()
     * @example return 'Online shipment tracking: <a href="link">link</a>';
     */
    public function tracking($tracking_id = null)
    {
        return '';
    }

    /**
     * Returns the list of printable forms this module offers
     * @example <pre> return array(
     *    'flatrate_form' => array(
     *        'name' => _wp('Sample consignment note'),
     *        'description' => _wp('Sample consignment description'),
     *    ),
     * );</pre>
     */
    public function getPrintForms()
    {
        return array();
    }

    /**
     * Displays the print form content (HTML).
     * Form id list is defined in getPrintForms() method
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        if ($id = 'flatrate_form') {
            $view = wa()->getView();
            $view->assign('order', $order);
            $view->assign('params', $params);
            $view->assign('plugin', $this);
            return $view->fetch($this->path.'/templates/form.html');
        } else {
            throw new waException($this->_w('Printable form not found'));
        }
    }

    /**
     * Sets mask for destination addresses that this shipping module allows shipping to
     */
    public function allowedAddress()
    {
        return array(

        );
    }

    public function requestedAddressFields()
    {
        return $this->prompt_address ? array() : false;
    }
}
