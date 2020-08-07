<?php

/**
 * Branch field: radio selector with javascript to hide parts of the form
 * depending on current selection.
 */
class waContactBranchField extends waContactSelectField
{
    public function getInfo()
    {
        $data = parent::getInfo();
        $data['branch_hide'] = ifempty($this->options, 'hide', array());
        return $data;
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        //
        // HTML: list of radio buttons
        //
        $value = isset($params['value']) ? $params['value'] : '';
        $html = '';
        $radios_name = $this->getHTMLName($params);
        foreach ($this->getOptions() as $k => $v) {
            $html .= '<label><input type="radio"'.(strlen($value) > 0 && $k == $value ? ' checked="checked"' : '').' name="'.$radios_name.'" value="'.htmlspecialchars($k).'"> '.htmlspecialchars($v).'</label>';
        }

        //
        // JS: hide form fields depending on radio selection
        //
        $hide_data = array();
        $hide_by_default = array();
        $field_names = array();
        $p = $params;
        $all_fields = waContactFields::getAll('enabled');

        foreach(ifempty($this->options['hide'], array()) as $option_id => $field_ids) {
            // Fool proofing (some app or plugin can write incorrect data in config)
            if (!is_array($field_ids)) {
                continue;
            }
            $hide_data[$option_id] = array_fill_keys($field_ids, 1);
            $hide_by_default += $hide_data[$option_id];
            foreach($field_ids as $fid) {
                if (empty($all_fields[$fid]) || $all_fields[$fid]->isRequired()) {
                    // Never hide required fields
                    unset($hide_by_default[$fid], $hide_data[$option_id][$fid]);
                    continue;
                }

                if (empty($field_names[$fid])) {
                    $p['id'] = $fid;
                    $field_names[$fid] = $this->getHTMLName($p);
                }
            }
        }

        $uniqid = uniqid('s');
        $hide_data['hide_by_default'] = $hide_by_default;
        $field_names = json_encode($field_names);
        $hide_data = json_encode($hide_data);

        $js = <<<EOF
<span id="{$uniqid}"></span>
<script>if ($) { $(function() { "use strict";

    var hide_data = {$hide_data};
    var radios_parent = $('#$uniqid').parent();
    var field_names = {$field_names};
    if (!radios_parent || !radios_parent.length) {
        return;
    }

    var initially_selected = radios_parent.find(':radio[name="{$radios_name}"]:checked');
    var previous_selection = 'hide_by_default';

    radios_parent.on('change', ':radio[name="{$radios_name}"]', function() {

        var option_id = $(this).val();

        // Show previously hidden
        if (hide_data[previous_selection]) {
            for (var field_id in hide_data[previous_selection]) {
                if (!hide_data[previous_selection].hasOwnProperty(field_id)) {
                    continue;
                }
                if (hide_data[option_id] && hide_data[option_id][field_id]) {
                    continue;
                }
                if (!field_names[field_id]) {
                    continue;
                }
                $('[name^="'+field_names[field_id]+'"]:first').closest('.wa-field,.field').show();
            }
        }

        // Hide using new selection
        if (hide_data[option_id]) {
            for (var field_id in hide_data[option_id]) {
                if (!hide_data[option_id].hasOwnProperty(field_id)) {
                    continue;
                }
                if (!field_names[field_id]) {
                    continue;
                }
                var field_to_hide = $('[name^="'+field_names[field_id]+'"]:first').closest('.wa-field,.field');
                if (!field_to_hide.is('.required,.wa-required')) {
                    field_to_hide.hide();
                }
            }
        }

        previous_selection = option_id;
    });

    if (initially_selected && initially_selected.length) {
        initially_selected.change();
    } else {
        var hide_by_default = hide_data.hide_by_default;
        for (var field_id in hide_by_default) {
            if (!hide_by_default.hasOwnProperty(field_id)) {
                continue;
            }
            if (!field_names[field_id]) {
                continue;
            }
            var field_to_hide = $('[name^="'+field_names[field_id]+'"]:first').closest('.wa-field,.field');
            if (!field_to_hide.is('.required,.wa-required')) {
                field_to_hide.hide();
            }
        }
    }
}); };</script>
EOF;

        return $html.$js;
    }
}

