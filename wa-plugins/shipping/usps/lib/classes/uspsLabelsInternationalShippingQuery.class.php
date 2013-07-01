<?php
/**
 * Send documents and packages globally. USPS® offers reliable, affordable shipping to more than 180 countries.
 * Generate Express Mail International®, Priority Mail International®,
 * First-Class Mail International®, or First-Class Package International Service shipping labels
 * complete with addresses, barcode, customs form, and mailing receipt.
 */

class uspsLabelsInternationalShippingQuery extends uspsLabelsQuery
{
    /**
     * @var string
     */
    protected $service;

    protected $token_to_services = array(
        'express'  => 'Express',
        'priority' => 'Priority',
        'first'    => 'FirstClass'
    );

    protected $services = array(
        'Express' => array(
            'id' => 'Express',
            'name' => 'Express Mail International',
            'api' => array(
                'test' => 'ExpressMailIntlCertify',
                'live' => 'ExpressMailIntl'
            )
        ),
        'Priority' => array(
            'id' => 'Priority',
            'name' => 'Priority Mail International',
            'api' => array(
                'test' => 'PriorityMailIntlCertify',
                'live' => 'PriorityMailIntl'
            ),
        ),
        'FirstClass' => array(
            'id' => 'FirstClass',
            'name' => 'First Class Mail International',
            'api' => array(
                'test' => 'FirstClassMailIntlCertify',
                'live' => 'FirstClassMailIntl'
            ),
        ),
    );

    public function __construct(uspsShipping $plugin, array $params)
    {
        $str = strtolower($params['service']);
        $service_name = '';
        foreach ($this->token_to_services as $token => $name) {
            if (strpos($str, $token) !== false) {
                $service_name = $name;
                break;
            }
        }
        if (!isset($this->services[$service_name])) {
            throw new waException(
                $plugin->_w("Unsupported service: ") . $params['service'] .
                $plugin->_w(". Supported services by this API are: ").
                implode(", ", $this->getSupportedServices())
            );
        }

        $this->service = $this->services[$service_name];

        if (empty($params['items'])) {
            throw new waException($plugin->_w("Empty items of order"));
        }

        $this->plugin = $plugin;
        $this->params = $params;

        $address = $this->getAddress();
        foreach ($address as $name => $value) {
            $address[$name] = $this->correctAddress($value);
        }
        $this->setAddress($address);
    }

    protected function getConfirmationNumberTagName()
    {
        return 'BarcodeNumber';
    }

    protected function getLabelTagName()
    {
        return 'LabelImage';
    }

    protected function getAPIName()
    {
        return $this->service['api'][($this->plugin->test_mode ? 'test' : 'live')];
    }

    public function getSupportedServices()
    {
        return array_keys($this->services);
    }

    /**
     * @return string
     */
    protected function prepareRequest()
    {
        $p = $this->plugin;

        $xml = new SimpleXMLElement("<{$this->getAPIName()}Request/>");
        $xml->addAttribute('USERID', $p->user_id);
        $xml->addChild('Option');
        $xml->addChild('Revision', 2);
        $xml->addChild('ImageParameters');
        $this->addSenderInfo($xml);
        $this->addRecipientInfo($xml);

        //$xml->addChild('Container', 'FLATRATEBOX');

        $this->addShippingContentsInfo($xml);

        if ($this->service['id'] != 'FirstClass') {
            $xml->addChild('InsuredAmount', $this->getPrice());
        }

        $xml->addChild('GrossPounds', $this->getWeight('pounds'));
        $xml->addChild('GrossOunces', $this->getWeight('ounces'));

        $xml->addChild('ContentType', $p->content_type);
        if ($p->content_type == 'OTHER') {
            $xml->addChild('ContentTypeOther', $p->other_content_type);
        }

        $xml->addChild('Agreement', 'Y');
        $xml->addChild('ImageType', 'PDF');
        $xml->addChild('ImageLayout', 'ALLINONEFILE');
        $xml->addChild('CustomerRefNo', $this->params['order_id']);
        if ($this->service['id'] != 'FirstClass' && $this->plugin->po_zip) {
            $xml->addChild('POZipCode', $this->plugin->po_zip);
        }

        $xml->addChild('Size', $p->package_size);

        // cut out xml header
        $xml = preg_replace("!^<\?xml .*?\?>!", "", $xml->saveXML());

        return $xml;
    }

    protected function getItems()
    {
        return $this->params['items'];
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function addSenderInfo(&$xml)
    {
        $p = $this->plugin;
        $xml->addChild('FromFirstName');
        $xml->addChild('FromMiddleInitial');
        $xml->addChild('FromLastName');
        $xml->addChild('FromFirm', $p->name);
        $xml->addChild('FromAddress1');
        $xml->addChild('FromAddress2', $p->address);
        $xml->addChild('FromCity',  $p->region_zone['city']);
        $xml->addChild('FromState', $p->region_zone['region']);

        $zip = $this->parseZip($p->zip);
        $xml->addChild('FromZip5', $zip['zip5']);
        $xml->addChild('FromZip4', $zip['zip4']);
        $xml->addChild('FromPhone', $p->phone);
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function addRecipientInfo(&$xml)
    {
        $name = $this->getAddress('name');
        $xml->addChild('ToFirstName');
        $xml->addChild('ToLastName');
        $xml->addChild('ToFirm', $name ? $name : 'Unknown name');
        $xml->addChild('ToAddress1');
        $xml->addChild('ToAddress2', $this->getAddress('street'));
        $xml->addChild('ToAddress3');

        $xml->addChild('ToCity', ucfirst($this->getAddress('city')));
        $xml->addChild('ToProvince', strtoupper($this->getAddress('region')));
        $xml->addChild('ToCountry', $this->getCountryName($this->getAddress('country')));
        $xml->addChild('ToPostalCode', $this->getAddress('zip'));
        $xml->addChild('ToPOBoxFlag', 'N');
        $xml->addChild('ToPhone');

        if ($this->service['id'] != 'FirstClass') {
            $xml->addChild('ToCustomsReference', $this->params['order_id']);
            $xml->addChild('NonDeliveryOption', 'RETURN');
        }
    }

    /**
     * @param SimpleXmlElement $xml
     */
    protected function addShippingContentsInfo(&$xml)
    {
        $contents = $xml->addChild('ShippingContents');
        foreach ($this->getItems() as $item) {
            $detail = $contents->addChild('ItemDetail');
            $detail->addChild('Description', $item['name']. ' - ' . $item['description']);
            $detail->addChild('Quantity', $item['quantity']);
            $detail->addChild('Value', round(isset($item['price']) ? $item['price'] : 0, 2));
            $weight = $this->parseWeight($item['weight']);
            $detail->addChild('NetPounds', $weight['pounds']);
            $detail->addChild('NetOunces', $weight['ounces']);
            $detail->addChild('HSTariffNumber');
            $detail->addChild('CountryOfOrigin');
        }
    }
}
