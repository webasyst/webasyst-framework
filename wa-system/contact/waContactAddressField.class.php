<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage contact
 */

class waContactAddressField extends waContactCompositeField
{

    protected function init()
    {
        if (!isset($this->options['fields'])) {
            $this->options['fields'] = array(
                new waContactStringField('street', 'Street address'),
                new waContactStringField('city', 'City'),
                new waContactRegionField('region', 'State'),
                new waContactStringField('zip', 'ZIP'),
                new waContactCountryField('country', 'Country', array(
                    'defaultOption' => 'Select country',
                )),
                new waContactHiddenField('lng', 'Longitude'),
                new waContactHiddenField('lat', 'Latitude'),
            );
        }
        if (!isset($this->options['formats']['js'])) {
            $this->options['formats']['js'] = new waContactAddressOneLineFormatter();
        }
        if (!isset($this->options['formats']['forMap'])) {
            $this->options['formats']['forMap'] = new waContactAddressForMapFormatter();
        }

        parent::init();
    }

    public function format($data, $format = null, $ignore_hidden = true)
    {
        if (!isset($data['value'])) {
            $value = array();
            foreach ($this->options['fields'] as $field) {
                $f_id = $field->getId();
                if ($ignore_hidden && $field instanceof waContactHiddenField) {
                    continue;
                }
                /**
                 * @var $field waContactField
                 */
                if (isset($data['data'][$f_id])) {
                    $tmp = trim($field->format($data['data'][$f_id], 'value', $data['data']));
                    if ($tmp) {
                        if (!in_array($f_id, array('country', 'region', 'zip', 'street', 'city'))) {
                            $tmp = $field->getName().' '.$tmp;
                        }
                        $value[] = $tmp;
                    }
                }
            }
            $data['value'] = implode(", ", array_filter($value, 'strlen'));
        }
        return parent::format($data, $format);
    }

    private function sendGeoCodingRequest($value)
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json';
        $params = array(
            'address' => $this->format($value, 'forMap'),
            'sensor'  => 'false'
        );
        $url = $url.'?'.http_build_query($params);
        $timeout = 25;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $content = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status == 200) {
                return $content;
            }
        } else {
            if (ini_get('allow_url_fopen')) {
                $wrappers = stream_get_wrappers();
                if (in_array('https', $wrappers)) {
                    $old_timeout = @ini_set('default_socket_timeout', $timeout);
                    $response = @file_get_contents($url);
                    @ini_set('default_socket_timeout', $old_timeout);
                    return $response;
                }
            }
        }
        return null;
    }

    private function setGeoCoords($value)
    {
        if (!isset($value['data'])) {
            return $value;
        }
        $sm = new waAppSettingsModel();
        $app_id = 'webasyst';
        $name = 'geocoding';
        $last_geocoding = $sm->get($app_id, $name, 0);
        if (time() - $last_geocoding >= 3600) {
            $response = $this->sendGeoCodingRequest($value);
            if ($response) {
                $response = json_decode($response, true);
                if ($response['status'] == "OK") {
                    $sm->del($app_id, $name);
                    foreach ($response['results'] as $result) {
                        if (empty($result['partial_match'])) {      // address correct, geocoding without errors
                            $value['data']['lat'] = ifset($result['geometry']['location']['lat'], '');
                            $value['data']['lng'] = ifset($result['geometry']['location']['lng'], '');
                            break;
                        }
                    }
                } else if ($response['status'] == "OVER_QUERY_LIMIT") {
                    $sm->set($app_id, $name, time());
                }
            }
        }
        return $value;
    }

    public function set(waContact $contact, $value, $params = array(), $add = false)
    {
        $result = parent::set($contact, $value, $params, $add);
        if (true || !empty($this->options['geocoding'])) {
            if (isset($result[0])) {
                foreach ($result as &$value) {
                    $value = $this->setGeoCoords($value);
                }
                unset($value);
            } else {
                $result = $this->setGeoCoords($result);
            }
        }
        return $result;
    }

}

class waContactAddressForMapFormatter extends waContactFieldFormatter
{
    public function format($data) {
        $res = array(
            'with_street' => '',
            'without_street' => ''
        );
        foreach ($res as $k => &$r) {
            $parts = array();
            foreach (waContactFields::get('address')->getFields() as $field) {
                /**
                 * @var waContactField $field
                 */
                $id = $field->getId();
                if (isset($data['data'][$id]) && trim($data['data'][$id])) {
                    $parts[$id] = $field->format($data['data'][$id], 'value', $data['data']);
                }
            }
            $p = $parts;
            $value = array();
            if (isset($parts['country'])) {
                $value[] = $p['country'];
                unset($p['country']);
            }
            if (isset($parts['region'])) {
                $value[] = $p['region'];
                unset($p['region']);
            }
            if (isset($parts['city']))
            {
                if (!isset($parts['region']) ||
                        mb_strtolower($parts['region']) != mb_strtolower($parts['city']))
                {
                    $value[] = $p['city'];
                }
                unset($p['city']);
            }
            if ($k === 'with_street') {
                if (isset($parts['street'])) {
                    $street = trim($parts['street']);
                    if (isset($data['data']['country']) && $data['data']['country'] === 'rus') {
                        if (preg_match('/[а-я]/iu', $street)) {
                            if (!preg_match('/^(улица|ул[\.\s])/iu', $street, $m)) {
                                $street = 'ул. ' . $street;
                            }
                        }
                    }
                    $value[] = $street;
                    unset($p['street']);
                }
            } else {
                if (isset($parts['street'])) {
                    unset($p['street']);
                }
            }
            foreach ($p as $v) {
                if ($v) {
                    $value[] = $v;
                }
            }
            $r = implode(',', $value);
        }
        unset($r);

        if (!empty($data['data']['lat']) && !empty($data['data']['lng'])) {
            $res['coords'] = str_replace(',', '.', $data['data']['lat']) . ", " . str_replace(',', '.', $data['data']['lng']);
        }

        return $res;
    }
}

/** Format address on one line. */
class waContactAddressOneLineFormatter extends waContactFieldFormatter
{
    public function format($data, $format = null)
    {
        $adr = waContactFields::get('address');
        $for_map = $adr->format($data, 'forMap');
        $parts = $this->getParts($data, $format);
        $data['value'] = implode(', ', $parts['parts']);
        if ($data['value'] && $parts['pic'] && (!isset($this->options['image']) || $this->options['image'])) {
            $data['value'] = $parts['pic'].' '.$data['value'];
        }
        if ($data['value'] && $parts['marker']) {
            $data['value'] .= ' '.$parts['marker'];
        }
        $data['for_map'] = $for_map;
        return $data;
    }

    protected function getParts($data, $format = null)
    {
        $result = array(
            // country flag image
            'pic'    => '',

            // marker with link to show on map
            'marker' => '',

            // parts of an address as subfield => value,
            // order as in options['fields'], empty subfields skipped
            'parts'  => array(),
        );

        $countryName = '';
//        $countryPic = '';
//        $searchLink = '';

        if (isset($data['data']['country']) && $data['data']['country']) {
            $model = new waCountryModel();
            $countryName = $model->name($data['data']['country']);
            // Do not show pic for unknown country
            if ($countryName) {
                $result['pic'] = '<img src="'.wa_url().'wa-content/img/country/'.strtolower($data['data']['country']).'.gif" class="overhanging" />';
            } else {
                $countryName = $format === 'value' ? htmlspecialchars($data['data']['country']) : $data['data']['country'];
            }
        }

        if (isset($data['data']['street']) || isset($data['data']['city']) || isset($data['data']['region']) || isset($data['data']['country']) || $countryName) {
            $searchURL = '';
            foreach (array('street', 'city', 'region') as $id) {
                if (!isset($data['data'][$id])) {
                    continue;
                }
                $searchURL .= ($searchURL ? ' ' : '').$data['data'][$id];
            }
            if ($countryName) {
                $searchURL .= ($searchURL ? ' ' : '').$countryName;
            }

            $searchURL = htmlspecialchars($searchURL);
            $result['marker'] = '<a href="http://mapof.it/'.$searchURL.'" class="small"><i class="icon16 marker"></i><b><i>'._ws('show on map').'</i></b></a>';
        }

        foreach (waContactFields::get('address')->getFields() as $field) {
            /**
             * @var waContactField $field
             */
            if ($field instanceof waContactHiddenField) {
                continue;
            }
            $id = $field->getId();
            if (isset($data['data'][$id]) && trim($data['data'][$id])) {
                if ($id === 'country') {
                    $result['parts'][$id] = $countryName;
                } else {
                    $result['parts'][$id] = $field->format($data['data'][$id], $format, $data['data']);
                }
                $result['parts'][$id] = htmlspecialchars($result['parts'][$id]);
                if (!in_array($id, array('country', 'region', 'zip', 'street', 'city'))) {
                    $result['parts'][$id] = '<span>'.$field->getName().'</span>' . ' ' . $result['parts'][$id];
                }
            }
        }
        if ((ifset($data['data']['country']) != 'usa') && (ifset($result['parts']['region']) == ifset($result['parts']['city']))) {
            unset($result['parts']['region']);
        }

        $result['marker'] = ''; // marker is disabled, but may be needed in future
        return $result;
    }
}

/** Format address so each subfield takes its own line.
 * Currently not used. */
class waContactAddressSeveralLinesFormatter extends waContactAddressOneLineFormatter
{
    public function format($data)
    {
        $parts = $this->getParts($data);

        $i = 0;
        $data['value'] = array();

        $fields = waContactFields::get('address')->getFields();
        foreach ($parts['parts'] as $part_id => $part) {
            $v = '';

            // add country flag before the first line
            if ($i === 0 && $parts['pic'] && (!isset($this->options['image']) || $this->options['image'])) {
                $v = $parts['pic'].' ';
            }

            $v .= $part;

            // add marker after the first line of address
            if ($i == 0 && $parts['marker']) {
                $v .= $parts['marker'];
            }

            $data['value'][] = $v;
            $i++;
        }

        $data['value'] = implode("<br>\n", $data['value']);
        return $data;
    }
}

// EOF
