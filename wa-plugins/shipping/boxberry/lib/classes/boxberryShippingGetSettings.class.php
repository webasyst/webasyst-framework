<?php

/**
 * Class boxberryShippingGetSettings
 */
class boxberryShippingGetSettings
{
    /**
     * @var boxberryShipping|null
     */
    protected $bxb = null;

    /**
     * boxberryShippingGetSettings constructor.
     * @param boxberryShipping $bxb
     */
    public function __construct(boxberryShipping $bxb)
    {
        $this->bxb = $bxb;
    }

    /**
     * @param array $params
     * @return string
     */
    public function getHtml($params = array())
    {
        $view = wa()->getView();
        $points_for_parcel = $this->getAllPointsForParcels();

        $view->assign(array(
            'obj'                => $this->bxb,
            'settings'           => $this->bxb->getSettings(),
            'namespace'          => waHtmlControl::makeNamespace($params),
            'points_for_parcel'  => $points_for_parcel,
            'points_by_settings' => $this->getPointsForParcelsBySettings($points_for_parcel),
            'regions'            => $this->getRegions(),
            'point_modes'        => $this->getPointModes(),
            'courier_modes'      => $this->getCourierModes(),
            'issuance_options'   => $this->getIssuanceOptions(),
        ));

        $path = $this->bxb->getPluginPath();
        $html = $view->fetch($path.'/templates/settings.html');
        return $html;
    }

    /**
     * Returns a saved pickup point
     *
     * @param $points_for_parcel
     * @return array
     */
    public function getPointsForParcelsBySettings($points_for_parcel)
    {
        $targetstart = $this->bxb->getSettings('targetstart');

        $result = [
            'targetstart' => $targetstart,
            'city'        => '',
            'points'      => []
        ];

        if ($targetstart) {
            foreach ($points_for_parcel as $city => $points) {
                foreach ($points as $point_data) {
                    if (ifset($point_data, 'code', false) == $targetstart) {
                        $result['city'] = $city;
                        $result['points'] = $points;
                        break 2;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAllPointsForParcels()
    {
        $handbook_manager = new boxberryShippingHandbookPointsForParcels($this->getApiManager());
        $points = $handbook_manager->getHandbook();

        return $points;
    }

    /**
     * @return array
     */
    protected function getRegions()
    {
        $rm = new waRegionModel();
        $regions = $rm->getByCountry('rus');

        return $regions;
    }

    /**
     * @return boxberryShippingApiManager
     */
    protected function getApiManager()
    {
        return new boxberryShippingApiManager($this->bxb->token, $this->bxb->api_url);
    }

    /**
     * @return array
     */
    public function getPointModes()
    {
        return array(
            array(
                'value' => 'off',
                'title' => $this->bxb->_w('Do not use'),
            ),
            array(
                'value' => 'all',
                'title' => $this->bxb->_w('All'),
            ),
            array(
                'value' => 'prepayment',
                'title' => $this->bxb->_w('With prepayment only'),
            ),
        );
    }

    /**
     * @return array
     */
    public function getCourierModes()
    {
        return array(
            array(
                'value' => 'off',
                'title' => $this->bxb->_w('Do not use'),
            ),
            array(
                'value' => 'all',
                'title' => $this->bxb->_w('All'),
            ),
            array(
                'value' => 'prepayment',
                'title' => $this->bxb->_w('With prepayment only'),
            ),
        );
    }

    /**
     * @return array
     */
    public function getIssuanceOptions()
    {
        return array(
            array(
                'value' => '0',
                'title' => $this->bxb->_w('Delivery without parcel opening'),
            ),
            array(
                'value' => '1',
                'title' => $this->bxb->_w('Delivery with parcel opening and completeness check'),
            ),
            array(
                'value' => '2',
                'title' => $this->bxb->_w('Delivery of a parcel part'),
            ),
        );
    }
}
