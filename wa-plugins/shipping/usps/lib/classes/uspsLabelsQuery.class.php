<?php

abstract class uspsLabelsQuery extends uspsQuery
{
    /**
     * @var array
     */
    protected $service;

    public function __construct(uspsShipping $plugin, array $params)
    {
        $service = uspsServices::getServiceByCode(
                !empty($params['service']) ? $params['service'] : ''
        );
        if (!$service) {
            throw new waException($plugin->_w("Unknown service"));
        }
        $supported = $this->getSupportedServices();
        if (is_array($supported) && !in_array($service['code'], $supported)) {
            throw new waException(
                    $plugin->_w("Unsupported service: ") . $service['code'] .
                    $plugin->_w(". Supported services by this API are: ").
                    implode(", ", $supported)
            );

        }
        $this->service = $service;

        parent::__construct($plugin, $params);
    }

    /**
     *
     * Correct address (e.g. transliterate)
     *
     * @param string $address
     * @return string
     */
    protected function correctAddress($address)
    {
        if ($address) {
            foreach (waLocale::getAll() as $lang) {
                $address = waLocale::transliterate($address, $lang);
            }
        }
        return $address;
    }

    protected function getUrl()
    {
        return 'https://secure.shippingapis.com/ShippingAPI.dll';
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function addSenderInfo(&$xml)
    {
        $p = $this->plugin;
        $xml->addChild('FromName', $p->name);
        $xml->addChild('FromFirm');
        $xml->addChild('FromAddress1');
        $xml->addChild('FromAddress2', $p->address);
        $xml->addChild('FromCity',  $p->region_zone['city']);
        $xml->addChild('FromState', $p->region_zone['region']);

        $zip = $this->parseZip($p->zip);
        $xml->addChild('FromZip5', $zip['zip5']);
        $xml->addChild('FromZip4', $zip['zip4']);
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function addRecipientInfo(&$xml)
    {
        $name = $this->getAddress('name');
        $xml->addChild('ToName', $name ? $name : 'Unknown name');
        $xml->addChild('ToFirm');
        $xml->addChild('ToAddress1');
        $xml->addChild('ToAddress2', $this->getAddress('street'));
        $xml->addChild('ToCity', ucfirst($this->getAddress('city')));
        $xml->addChild('ToState', strtoupper($this->getAddress('region')));
        $zip = $this->parseZip($this->getAddress('zip'));
        $xml->addChild('ToZip5', $zip['zip5']);
        $xml->addChild('ToZip4', $zip['zip4']);
    }

    abstract protected function getConfirmationNumberTagName();

    abstract protected function getLabelTagName();

    abstract public function getSupportedServices();

    /**
     * @see uspsQuery::parseResponse()
     */
    protected function parseResponse($response)
    {
        try {
            $xml = new SimpleXMLElement($response);
        } catch (Exception $ex) {
            throw new waException($this->plugin->_w("Xml isn't well-formed"));
        }
        if ($xml->getName() == 'Error') {
            throw new waException((string) $xml->Description, (int) $xml->Number);
        }

        $number = $xml->xpath($this->getConfirmationNumberTagName());
        if (empty($number)) {
            throw new waException("Usps return empty confirmation number");
        }

        $label = $xml->xpath($this->getLabelTagName());
        if (empty($label)) {
            throw new waException("Usps return empty printing label");
        }

        return array(
                'confirmation_number' => (string) $number[0],
                'label'       => base64_decode((string) $label[0]),
        );
    }

    /**
     * Taking into account zip+4 format
     */
    public function parseZip($zip)
    {
        // tacking into account zip+4 format
        $zip = array('zip5' => $zip);
        $zip['zip5'] = trim($zip['zip5']);
        if (preg_match('/(\d{5})([-\s]*(\d{4}))*/', $zip['zip5'], $m)) {
            $zip['zip5'] = $m[1];
            $zip['zip4'] = isset($m[3]) ? $m[3] : null;
        } else {
            $zip['zip4'] = null;
        }
        return $zip;
    }
}
