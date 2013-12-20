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
            );
        }
        if (!isset($this->options['formats']['js'])) {
            $this->options['formats']['js'] = new waContactAddressOneLineFormatter();
        }
    }

    public function format($data, $format = null)
    {
        if (!isset($data['value'])) {
            $value = array();
            foreach ($this->options['fields'] as $field) {
                /**
                 * @var $field waContactField
                 */
                if (isset($data['data'][$field->getId()])) {
                    $tmp = trim($field->format($data['data'][$field->getId()], 'value', $data['data']));
                    if ($tmp) {
                        if (!in_array($field->getId(), array('country', 'region', 'zip', 'street', 'city'))) {
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
}

/** Format address on one line. */
class waContactAddressOneLineFormatter extends waContactFieldFormatter
{
    public function format($data, $format=null) {
        $parts = $this->getParts($data, $format);
        $data['value'] = implode(', ', $parts['parts']);
        if ($data['value'] && $parts['pic'] && (!isset($this->options['image']) || $this->options['image'])) {
            $data['value'] = $parts['pic'].' '.$data['value'];
        }
        if ($data['value'] && $parts['marker']) {
            $data['value'] .= ' '.$parts['marker'];
        }
        return $data;
    }

    protected function getParts($data, $format=null)
    {
        $result = array(
            // country flag image
            'pic' => '',

            // marker with link to show on map
            'marker' => '',

            // parts of an address as subfield => value,
            // order as in options['fields'], empty subfields skipped
            'parts' => array(),
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
            }
        }

        if (isset($data['data']['street']) || isset($data['data']['city']) || isset($data['data']['region']) || isset($data['data']['country']) || $countryName) {
            $searchURL = '';
            foreach (array('street', 'city', 'region') as $id) {
                if (!isset($data['data'][$id])) {
                    continue;
                }
                $searchURL .= ($searchURL ? ' ' : '') . $data['data'][$id];
            }
            if ($countryName) {
                $searchURL .= ($searchURL ? ' ' : '') . $countryName;
            }

            $searchURL = htmlspecialchars($searchURL);
            $result['marker'] = '<a href="http://mapof.it/'.$searchURL.'" class="small"><i class="icon16 marker"></i><b><i>'._w('show on map').'</i></b></a>';
        }

        foreach (waContactFields::get('address')->getFields() as $field) {
            /**
             * @var waContactField $field
             */
            $id = $field->getId();
            if (isset($data['data'][$id]) && trim($data['data'][$id])) {
                if ($id === 'country') {
                    $result['parts'][$id] = $countryName;
                } else {
                    $result['parts'][$id] = $field->format($data['data'][$id], $format, $data['data']);
                }
                $result['parts'][$id] = htmlspecialchars($result['parts'][$id]);
                if (!in_array($id, array('country', 'region', 'zip', 'street', 'city'))) {
                    $result['parts'][$id] = $field->getName().' '.$result['parts'][$id];
                }
            }
        }

        $result['marker'] = ''; // marker is disabled, but may be needed in future
        return $result;
    }
}

/** Format address so each subfield takes its own line.
  * Currently not used. */
class waContactAddressSeveralLinesFormatter extends waContactAddressOneLineFormatter
{
    public function format($data) {
        $parts = $this->getParts($data);
        $i = 0;
        $data['value'] = array();

        $fields = waContactFields::get('address')->getFields();
        foreach($parts['parts'] as $part_id => $part) {
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
