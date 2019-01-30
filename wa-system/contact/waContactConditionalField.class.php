<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * Conditional field: show <select> with options from wa_contact_field_values, when parent field has known value;
 * otherwise, show <input type="text">.
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage contact
 */
class waContactConditionalField extends waContactField
{
    public function getInfo()
    {
        $info = parent::getInfo();
        $tmp = $this->getOptions();
        $info['parent_options'] = reset($tmp);
        $info['parent_field'] = key($tmp);
        $info['hide_unmatched'] = $this->getParameter('hide_unmatched') && !$this->isRequired() ? true : false;
        return $info;
    }

    public function getOptions()
    {
        $id = $this->getId();
        $parent = $this->getParameter('parent_id');
        if ($parent) {
            $parent = explode('.', $parent, 2);
            $parent = $parent[0];
            $id = $parent.':'.$id;
        }
        static $cfdm = null;
        if (!$cfdm) {
            $cfdm = new waContactFieldValuesModel();
        }
        $result = array();
        foreach ($cfdm->where('field=?', $id)->order('sort')->query() as $row) {
            $result[$row['parent_field']][mb_strtolower($row['parent_value'])][] = $row['value'];
        }
        return $result;
    }

    protected function getInputHtml($name_input, $value, $attrs)
    {
        $name_html = $name_input === null ? ' style="display:none"' : ' name="'.htmlspecialchars($name_input).'"';
        $value_html = $value === null ? '' : ' value="'.htmlspecialchars($value).'"';
        $attrs = $attrs ? ' '.$attrs : '';
        return '<input type="text"'.$name_html.$value_html.$attrs.'>';
    }

    protected function getSelectHtml($name_input, $value, $attrs, $options)
    {
        $opts = array();
        if ($options) {

            if ($this->isRequired()) {
                $opts[] = '<option value=""></option>';
            }

            foreach ($options as $option_value) {
                $at = ($value !== null && $value == $option_value) ? ' selected' : '';
                $option_value = htmlspecialchars($option_value);
                $opts[] = '<option value="'.$option_value.'"'.$at.'>'.$option_value.'</option>';
            }
        }
        $name_html = $name_input === null ? ' style="display:none"' : ' name="'.htmlspecialchars($name_input).'"';
        $attrs = $attrs ? ' '.$attrs : '';
        return '<select'.$name_html.$attrs.">\n\t".implode("\n\t", $opts)."\n</select>";
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

        // When we're a part of a composite field, show <select> or <input>
        // depending on values of other fields.
        // Otherwise, show a simple <input>.
        $html = '';
        $parent_field = null;
        $possible_options = $this->getOptions();
        if (empty($params['composite_value'])) {
            $html = $this->getInputHtml($name_input, $value, $attrs);
            $html .= $this->getSelectHtml(null, null, $attrs, null);
        } else {
            foreach($possible_options as $p_fld => $parent_values) {
                if (!empty($params['composite_value'][$p_fld])) {
                    $parent_field = $p_fld;
                    break;
                }
            }
            if ($parent_field === null) {
                $html = $this->getInputHtml($name_input, $value, $attrs);
                $html .= $this->getSelectHtml(null, null, $attrs, null);
            } else {
                $parent_value = $params['composite_value'][$p_fld];
                if (empty($possible_options[$parent_field][$parent_value])) {
                    $html = $this->getInputHtml($name_input, $value, $attrs);
                    $html .= $this->getSelectHtml(null, null, $attrs, null);
                } else {
                    $html = $this->getInputHtml(null, null, $attrs);
                    $html .= $this->getSelectHtml($name_input, $value, $attrs, $possible_options[$parent_field][$parent_value]);
                }
            }
        }
        if (!$parent_field && $possible_options) {
            reset($possible_options);
            $parent_field = key($possible_options);
        }

        // JS to change field HTML when user changes country.
        $js = '';
        if ($parent_field && !empty($possible_options[$parent_field])) {
            $p = $params;
            $p['id'] = explode(':', $parent_field);
            $p['id'] = array_pop($p['id']);
            $name_parent = $this->getHTMLName($p);
            $values = json_encode($possible_options[$parent_field]);
            $option_hide_unmatched = $this->getParameter('hide_unmatched') && !$this->isRequired() ? 'true' : 'false';
            $show_empty_option = $this->isRequired() ? 'true' : 'false';

            $js = <<<EOJS
<script>if($){ $(function() {
    var parent_field_selector = '[name="{$name_parent}"]';
    var parent_field = $(parent_field_selector);
    if (parent_field.length <= 0) {
        return;
    }
    var input_name = "{$name_input}";
    var values = {$values};
    var select;
    var input = $('[name="'+input_name+'"]');
    if (input.length <= 0) {
        return;
    }
    if (input.is('input')) {
        select = input.next();
    } else {
        select = input;
        input = select.prev();
    }

    var showInput = function() {
        if (!input[0].hasAttribute('name')) {
            input.attr('name', select.attr('name'))
            select[0].removeAttribute('name');
        }
        input.show().val('');
        select.hide();
    };

    var getVal = function() {
        if (input.is(':visible')) {
            return input.val();
        } else {
            return select.val();
        }
    };

    // Parent field on-change handler
    var handler = function() {
        var old_val = getVal();
        var parent_value = $(this).val().toLowerCase();
        if ({$option_hide_unmatched}) {
            input.closest('.field').show();
        }
        if (values && values[parent_value]) {
            var options = values[parent_value];
            input.hide();
            select.show().children().remove();
            if ({$show_empty_option}) {
                select.append($('<option value=""></option>'));
            }
            for (i = 0; i < options.length; i++) {
                select.append($('<option></option>').attr('value', options[i]).text(options[i]));
            }
            select.val(old_val);
            if (input[0].hasAttribute('name')) {
                select.attr('name', input.attr('name'));
                input[0].removeAttribute('name');
            }
        } else if ({$option_hide_unmatched}) {
            showInput();
            input.val('');
            input.closest('.field').hide();
        } else {
            if (!input.is(':visible')) {
                showInput();
                input.val(old_val);
            }
        }
    };
    handler.call(parent_field);

    var wrapper = parent_field.closest('.field');
    if (wrapper.length) {
        wrapper.on('change', parent_field_selector, handler);
    } else {
        parent_field.change(handler);
    }
});};</script>
EOJS;
        }

        return $html.$js;
    }
}

