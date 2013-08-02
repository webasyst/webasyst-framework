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
class waContactRegionField extends waContactField
{
    protected $rm = null;
    public function getInfo()
    {
        $data = parent::getInfo();
        $data['region_countries'] = array_fill_keys($this->getRegionCountries(), 1);
        return $data;
    }

    public function getRegionCountries()
    {
        if (!$this->rm) {
            $this->rm = new waRegionModel();
        }
        static $region_countries = null;
        if ($region_countries === null) {
            $region_countries = $this->rm->getCountries();
        }
        return $region_countries;
    }

    public function format($data, $format = null, $full_composite=null)
    {
        if (empty($full_composite['country'])) {
            return $format === 'value' ? htmlspecialchars($data) : $data;
        }
        if (!$this->rm) {
            $this->rm = new waRegionModel();
        }
        $row = $this->rm->get($full_composite['country'], $data);
        if (!$row) {
            return $format === 'value' ? htmlspecialchars($data) : $data;
        }
        return $format === 'value' ? htmlspecialchars($row['name']) : $row['name'];
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        $value = isset($params['value']) ? $params['value'] : '';
        $ext = null;
        $multi_suffix = '';
        if (is_array($value)) {
            $ext = $value['ext'];
            $value = $value['value'];
        }

        $name_input = $name = $this->getHTMLName($params);
        if ($this->isMulti()) {
            $name_input .= '[value]';
        }

        $country = ifset($params['composite_value']['country']);
        $region_countries = array_fill_keys($this->getRegionCountries(), 1);
        if (!$region_countries || (empty($country) && empty($params['parent']))) {
            // The simplest case: just show <input> with no logic at all.
            return '<input type="text" name="'.htmlspecialchars($name_input).'" value="'.htmlspecialchars($value).'" '.$attrs.'>';
        }

        //
        // So, we're a part of a composite field with a Country subfield.
        // Need to show <select> with regions, if selected country has them,
        // or <input> when no country selected or has no regions.
        // In case user changes the country, we should load new regions via XHR.
        // And on top of that, field should behave reasonably when JS is off!
        //

        // When country is selected and has regions, build a <select> with appropriate options.
        $region_select = null;
        if ($country && !is_array($country)) {
            // List of regions for this country
            $rm = new waRegionModel();
            $options = array(
                '<option value="">'.htmlspecialchars('<'._ws('select region').'>').'</option>',
            );
            $selected = false;
            foreach($rm->getByCountryWithFav($country) as $row) {
                if (strlen($row['name']) <= 0) {
                    $options[] = '<option disabled>&nbsp;</option>';
                } else {
                    if (!$selected && ($value == $row['code'] || $value == $row['name'])) {
                        $at = ' selected';
                        $selected = true;
                    } else {
                        $at = '';
                    }
                    $options[] = '<option value="'.htmlspecialchars($row['code']).'"'.$at.'>'.htmlspecialchars($row['name']).'</option>';
                }
            }

            if (count($options) > 1) {
                // Selected country has regions. Show as <select>.
                $region_select = '<select name="'.htmlspecialchars($name_input).'" '.$attrs.">\n\t".implode("\n\t", $options)."\n</select>";
            }
        }

        $html = '';
        if ($region_select) {
            // Selected country has regions. Select field with regions is visible.
            // There's a hidden <input> to switch to when user changes country.
            $html .= $region_select;
            $html .= '<input type="text" '.$attrs.' style="display:none;">';
        } else {
            // No country selected or country has no regions.
            // <input> is visible and <select> is hidden.
            $html .= '<select '.$attrs.' style="display:none;"></select>';
            $html .= '<input type="text" name="'.htmlspecialchars($name_input).'" value="'.htmlspecialchars($value).'" '.$attrs.'>';
        }

        // JS to load regions when user changes country.
        $p = $params;
        $p['id'] = 'country';
        $name_country = $this->getHTMLName($p);
        if (wa()->getEnv() == 'backend') {
            $xhr_url = ifset($params['xhr_url'], wa()->getAppUrl('webasyst').'?module=backend&action=regions');
        } else {
            $xhr_url = ifset($params['xhr_url'], wa()->getRouteUrl('/frontend/regions'));
        }
        $region_countries = json_encode($region_countries);
        $empty_option = '<'._ws('select region').'>';
        $js = <<<EOJS
<script>if($){ $(function() {
    var region_countries = {$region_countries};
    var input_name = "{$name_input}";
    var country_select = $('[name="{$name_country}"]');
    var xhr_url = "{$xhr_url}";
    if (country_select.length <= 0) {
        return;
    }

    var select;
    var input = $('[name="'+input_name+'"]');
    if (input.length <= 0) {
        return;
    }
    if (input.is('input')) {
        select = input.prev();
    } else {
        select = input;
        input = select.next();
    }

    var showInput = function(val) {
        if (!input[0].hasAttribute('name')) {
            input.attr('name', select.attr('name'))
            select[0].removeAttribute('name');
        }
        input.show().val(val || '');
        select.hide();
    };

    var getVal = function() {
        if (input.is(':visible')) {
            return input.val();
        } else {
            return select.val();
        }
    };

    var change_handler;
    country_select.change(change_handler = function() {
        var old_val = getVal();
        var country = country_select.val();
        input.prev('.loading').remove();
        if (region_countries && region_countries[country]) {
            showInput('');
            input.before('<i class="icon16 loading"></i>');
            $.post(xhr_url, { country: country }, function(r) {
                input.prev('.loading').remove();
                if (r.data && r.data.options && r.data.oOrder) {
                    input.hide();
                    select.show().children().remove();
                    select.append($('<option value=""></option>').text("{$empty_option}"));
                    var o, selected = false;
                    for (i = 0; i < r.data.oOrder.length; i++) {
                        o = $('<option></option>').attr('value', r.data.oOrder[i]).text(r.data.options[r.data.oOrder[i]]).attr('disabled', r.data.oOrder[i] === '');
                        if (!selected && old_val === r.data.oOrder[i]) {
                            o.attr('selected', true);
                            selected = true;
                        }
                        select.append(o);
                    }
                    if (input[0].hasAttribute('name')) {
                        select.attr('name', input.attr('name'));
                        input[0].removeAttribute('name');
                    }
                } else {
                    showInput('');
                }
            }, 'json');
        } else {
            if (!input.is(':visible')) {
                showInput('');
            }
        }
    });
    change_handler.call(country_select);
});};</script>
EOJS;

        return $html.$js;
    }
}

