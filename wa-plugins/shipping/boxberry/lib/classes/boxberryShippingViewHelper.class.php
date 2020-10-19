<?php

class boxberryShippingViewHelper
{
    /**
     * @var boxberryShipping|null
     */
    protected $bxb = null;

    /**
     * boxberryShippingViewHelper constructor.
     * @param boxberryShipping $bxb
     */
    public function __construct(boxberryShipping $bxb)
    {
        $this->bxb = $bxb;
    }

    /**
     * Returns all information for the point of receipt of orders
     *
     * @param $order
     * @return array
     * @throws waException
     */
    public function getInfo($order)
    {
        $result = [];
        $rate_id = ifset($order, 'params', 'shipping_rate_id', false);
        $rate_data = explode(boxberryShippingCalculatePoints::getVariantSeparator(), $rate_id);

        $code = ifset($rate_data, 1, false);

        if (!is_array($rate_data) || $rate_data[0] != boxberryShippingCalculatePoints::VARIANT_PREFIX || empty($code)) {
            return $result;
        }

        // Get the usual information
        $result = $this->getPoint($code);

        if ($result) {
            // Add a photo, full schedule, etc.
            $result = array_merge($result, $this->getExtendedPoint($code));
        }

        return $result;
    }

    /**
     * @param $code
     * @return array
     */
    protected function getPoint($code)
    {
        $handbook = new boxberryShippingHandbookAvailablePoints($this->getApiManger());
        return $handbook->getPointByCode($code);
    }

    /**
     * @param $code
     * @return array
     * @throws waException
     */
    protected function getExtendedPoint($code)
    {
        $handbook = new boxberryShippingHandbookPointDescription($this->getApiManger(), ['code' => $code, 'id'=> $this->bxb->getId()]);
        return $handbook->getHandbook();
    }

    /**
     * @return boxberryShippingApiManager
     */
    protected function getApiManger()
    {
        return new boxberryShippingApiManager($this->bxb->token, $this->bxb->api_url, $this->bxb);
    }
}
