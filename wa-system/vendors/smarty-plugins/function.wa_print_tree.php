<?php

function smarty_function_wa_print_tree($params, $template)
{
    $data = $params['tree'];

    $html = '<ul class="menu-v'.(isset($params['class']) ? ' '.$params['class'] : '').'"'.(isset($params['attrs']) ? ' '.$params['attrs'] : '').'>';
    if (isset($params['attrs'])) {
        unset($params['attrs']);
    }
    if (isset($params['class'])) {
        unset($params['class']);
    }
    preg_match_all('/:([a-z_]+)/', $params['elem'], $match);
    foreach ($data as $row) {
        $html .= '<li'.(isset($params['selected']) && $row['id'] == $params['selected'] ? ' class="selected"' : '').'>';
        $elem = $params['elem'];
        foreach ($match[1] as $k) {
            if (isset($row[$k])) {
                $elem = str_replace(':'.$k, $row[$k], $elem);
            }
        }
        $html .= $elem;
        if (!empty($row['childs'])) {
            if (!isset($params['depth']) || $params['depth']) {
                if (isset($params['depth'])) {
                    $params['depth']--;
                }
                $params['tree'] = $row['childs'];
                $html .= smarty_function_wa_print_tree($params, $template);
                if (isset($params['depth'])) {
                    $params['depth']++;
                }
            }
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}