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
class waContactCountryField extends waContactSelectField
{
    /**
     * @var waCountryModel
     */
    protected $model = null;

    public function init()
    {
        if (!isset($this->options['formats']['value'])) {
            $this->options['formats']['value'] = new waContactCountryFormatter();
        }
    }

    public function prepareVarExport()
    {
        $this->model = null;
    }

    public function format($data, $format = null)
    {
        $res = parent::format($data, $format);
        if (!$res) {
            return $format === 'value' ? htmlspecialchars($data) : $data;
        }
        return $res;
    }
    
    public function getOptions($id = null)
    {
        if (isset($this->options['options']) && is_array($this->options['options'])) {
            return $this->options['options'];
        }
        if (!$this->model) {
            $this->model = new waCountryModel();
        }
        if ($id) {
            if (! ( $result = $this->model->name($id))) {
                throw new Exception('Unknown country ISO-3 code: '.$id);
            }
            return $result;
        }

        $result = $this->model->all();
        foreach($result as &$row) {
            $row = $row['name'];
        }

        // Config option to show subset of countries only
        if (isset($this->options['iso_codes']) && is_array($this->options['iso_codes'])) {
            $result = array_intersect_key($result, array_fill_keys($this->options['iso_codes'], true));
        }

        return $result;
    }

    public function getType()
    {
        return 'Country';
    }

    public function getInfo()
    {
        $data = parent::getInfo();
        $data['oOrder'] = array();

        if (isset($this->options['iso_codes']) && is_array($this->options['iso_codes'])) {
            $iso_codes = array_flip($this->options['iso_codes']);
        } else {
            $iso_codes = null;
        }

        foreach($this->model->allWithFav() as $c) {
            if (!$iso_codes || isset($iso_codes[$c['iso3letter']]) || $c['iso3letter'] === '') {
                $data['oOrder'][] = $c['iso3letter'];
            }
        }
        $data['options'][''] = ' ';
        return $data;
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        if (!$this->model) {
            $this->model = new waCountryModel();
        }
        $url = wa()->getRootUrl().'wa-content/img/country/';
        $id = 'wa-country-field-'.uniqid();

        if (!isset($params['value'])) {
            // Try to guess country using locale
            static $default_country = null;
            if ($default_country === null) {
                $c = $this->model->getByField('iso2letter', strtolower(substr(wa()->getLocale(), -2)));
                if ($c) {
                    $default_country = $c['iso3letter'];
                }
            }
            $params['value'] = $default_country;
        }

        if (isset($this->options['iso_codes']) && is_array($this->options['iso_codes'])) {
            $iso_codes = array_flip($this->options['iso_codes']);
        } else {
            $iso_codes = null;
        }

        $selected = false;
        $value = isset($params['value']) ? $params['value'] : '';
        $html = '<select '.$attrs.' name="'.$this->getHTMLName($params).'"><option value=""></option>';
        foreach ($this->model->allWithFav() as $v) {
            if (!$iso_codes || isset($iso_codes[$v['iso3letter']]) || $v['iso3letter'] === '') {
                if ($v['name'] === '') {
                    $html .= '<option disabled>&nbsp;</option>';
                } else {
                    if (!$selected && $v['iso3letter'] == $value) {
                        $at = ' selected';
                        $selected = true;
                    } else {
                        $at = '';
                    }
                    $html .= '<option value="'.htmlspecialchars($v['iso3letter']).'"'.$at.'>'.htmlspecialchars($v['name']).'</option>';
                }
            }
        }
        $html .= '</select>';

        $html = '<i style="display:none" class="icon16" style=""></i>'.$html;
        $html .= '<script>if ($) { $(function() { "use strict";
            var select = $("#'.$id.'");
            var f = function () {
                if (select.val()) {
                    select.prev().show().css("background", "url('.$url.'" + select.val() + ".gif) 0 center no-repeat");
                } else {
                    select.prev().hide();
                }
            };
            f.call(select[0]);
            select.change(f);
        }); };</script>';
        return $html;
    }
}

class waContactCountryFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        return waCountryModel::getInstance()->name($data);
    }
}
