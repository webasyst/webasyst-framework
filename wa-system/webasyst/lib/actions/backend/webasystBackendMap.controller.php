<?php
/** @since 4.0.0 */
class webasystBackendMapController extends waJsonController
{
    private $adapter;
    private $map_options;

    public function execute()
    {
        $method = waRequest::request('method');
        $address = waRequest::request('address');
        $width = waRequest::request('width', 100, waRequest::TYPE_INT);
        $height = waRequest::request('height', 100, waRequest::TYPE_INT);
        $zoom = waRequest::request('zoom', 13, waRequest::TYPE_INT);

        if (!wa()->getUser()->isAdmin()) {
            $this->setError(_w('Access denied'));
            return null;
        } elseif (empty($method)) {
            $this->setError(_w('Unknown method'));
            return null;
        }

        $model = new waAppSettingsModel();
        $this->adapter = $model->get('webasyst', 'backend_map_adapter', 'yandex');
        $this->map_options = [
            'width'   => $width.'px',
            'height'  => $height.'px',
            'zoom'    => $zoom,
            'static'  => true,
            'on_error' => '',
        ];

        switch ($method) {
            case 'address':
                if (empty($address)) {
                    $this->setError(_w('Empty address.'));
                    return null;
                }
                $this->getMapByAddress($address);
                break;
            case 'geocode':
                if (empty($address)) {
                    $this->setError(_w('Empty address.'));
                    return null;
                }
                $this->getGeocodeByAddress($address);
                break;
            default:
                $this->setError(_w('Unknown method'));
        }
    }

    private function getGeocodeByAddress($address)
    {
        try {
            $this->response['geocode'] = wa()->getMap($this->adapter)->geocode($address);
        } catch (Exception $e) {
            $this->setError(_ws('Failed to get the coordinates.'));
        }
    }

    private function getMapByAddress($address)
    {
        try {
            $this->getGeocodeByAddress($address);
            $this->response['map_html'] = wa()->getMap($this->adapter)->getHTML($address, $this->map_options);
        } catch (Exception $e) {
            $this->setError(_w('Failed to get the address.'));
        }
    }
}
