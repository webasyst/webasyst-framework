 <?php

class uspsTrackingQuery extends uspsQuery
{
    public function __construct(uspsShipping $plugin, array $params) {
        // tracking ID is obligatory
        if (!isset($params['tracking_id'])) {
            throw new waException($plugin->_w("Empty tracking ID"));
        }
        parent::__construct($plugin, $params);
    }

    protected function getAPIName()
    {
        return 'TrackV2';
    }

    protected function getUrl()
    {
        return 'http://production.shippingapis.com/ShippingAPI' . (
                $this->plugin->test_mode ? 'Test.dll' : '.dll'
            );
    }

    /**
     * @see uspsQuery::prepareRequest()
     */
    protected function prepareRequest()
    {
        $xml = new SimpleXMLElement('<TrackRequest/>');
        $xml->addAttribute('USERID', $this->plugin->user_id);
        $track = $xml->addChild('TrackID');
        if ($this->plugin->test_mode) {
            // test request
            // @see https://www.usps.com/business/web-tools-apis/delivery-information.htm
            $track->addAttribute('ID', 'EJ958083578US');
        } else {
            $track->addAttribute('ID', $this->params['tracking_id']);
        }
        return $xml->saveXML();
    }

    /**
     * @see uspsQuery::parseResponse()
     */
    protected function parseResponse($response)
    {
        try {
            $xml = new SimpleXMLElement($response);
        } catch (Exception $e) {
            throw new waException($this->plugin->_w("Xml isn't well-formed"));
        }

        $response = '';
        switch ($xml->getName()) {
            case 'TrackResponse':
                $response.= '<strong>'.implode('<br>', $xml->xpath('TrackInfo/TrackSummary')).'</strong><br><br>';
                $response.= implode('<br>', $xml->xpath('TrackInfo/TrackDetail'));
                break;
            case 'Error':
                throw new waException((string) $xml->Description, (int) $xml->Number);
                break;
        }

        return $response;
    }
}