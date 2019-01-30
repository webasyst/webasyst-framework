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
                /**
                 * @var $field waContactField
                 */
                $f_id = $field->getId();
                if ($ignore_hidden && $field instanceof waContactHiddenField) {
                    continue;
                }

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

    private function setGeoCoords($value)
    {
        if (!isset($value['data'])) {
            return $value;
        }

        try {
            $map = wa()->getMap();
            $address = $this->format($value, 'forMap');
            $data = null;
            if (!empty($address['with_street'])) {
                $data = $map->geocode($address['with_street']);
            }
            if (empty($data) && !empty($address['without_street'])) {
                $data = $map->geocode($address['without_street']);
            }
            if ($data) {
                $value['data'] = array_merge($value['data'], $data);
            }
        } catch (waException $ex) {
            waLog::log("waContactAddressField->setGeoCoords(): ".$ex->getMessage()."\n".$ex->getFullTraceAsString(), 'geocode.log');
        }
        return $value;
    }

    public function prepareSave($value, waContact $contact = null)
    {
        if (isset($value[0])) {
            foreach ($value as &$v) {
                $v = $this->setGeoCoords($v);
            }
            unset($v);
        } else {
            $value = $this->setGeoCoords($value);
        }
        return parent::prepareSave($value, $contact);
    }
}

class waContactAddressForMapFormatter extends waContactFieldFormatter
{
    public function format($data) {
        $res = array(
            'with_street' => '',
            'without_street' => ''
        );
        $parts = array();
        foreach (waContactFields::get('address')->getFields() as $field) {
            /**
             * @var waContactField $field
             */
            $id = $field->getId();
            if (!in_array($id, array('lat', 'lng')) && isset($data['data'][$id]) && trim($data['data'][$id])) {
                $parts[$id] = $field->format($data['data'][$id], 'value', $data['data']);
            }
        }

        foreach ($res as $k => &$r) {

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
            if (isset($parts['city'])) {
                if (!isset($parts['region']) || (mb_strtolower($parts['region']) != mb_strtolower($parts['city']))) {
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
    public function format($data)
    {
        $adr = waContactFields::get('address');
        $for_map = $adr->format($data, 'forMap');
        $parts = $this->getParts($data);
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
        $city = isset($result['parts']['city']) ? $result['parts']['city'] : null;
        $region = isset($result['parts']['region']) ? $result['parts']['region'] : null;
        if (ifset($data, 'data', 'country', null) != 'usa' && $city == $region) {
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

class waContactAddressDataFormatter extends waContactAddressOneLineFormatter
{
    public function format($data)
    {
        $parts = $this->getParts($data);
        $data['value'] = array();
        foreach ($parts['parts'] + $data['data'] as $key => $value) {
            if (strlen($value)) {
                $data['value'][$key] = $value;
            }
        }
        unset($data['value']['lat'], $data['value']['lng']);

        $adr = waContactFields::get('address');
        $data['for_map'] = $adr->format($data, 'forMap');
        return $data;
    }
}

// EOF
