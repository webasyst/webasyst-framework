<?php

function smarty_function_wa_print_tree($params, &$smarty)
{
    $data = $params['tree'];

    $unfolded = isset($params['unfolded']) ? $params['unfolded'] : true;
    if (!$unfolded) {
        $params['depth'] = 0;
        if (!empty($params['selected'])) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveArrayIterator($data)
            );
            $depth = 0;
            $params['expanded'] = array();
            foreach ($iterator as $k => $id) {
                if ($k == 'id') {
                    $d = $iterator->getDepth();
                    while ($d <= $depth) {
                        array_pop($params['expanded']);
                        $depth -= 2;
                    }
                    $params['expanded'][] = $id;
                    $depth = $d;
                    if ($id == $params['selected']) {
                        break;
                    }
                }
            }
        }
        unset($params['unfolded']);
    }


    $html = '<ul class="menu-v'.(isset($params['class']) ? ' '.$params['class'] : '').'"'.(isset($params['attrs']) ? ' '.$params['attrs'] : '').'>';
    if (isset($params['attrs'])) {
        unset($params['attrs']);
    }
    if (isset($params['class'])) {
        unset($params['class']);
    }
    preg_match_all('/:([a-z_]+(?:\.[a-z]+)?)/', $params['elem'], $match);

    foreach ($data as $row) {
        $li_classes = array();
        if (isset($params['selected']) && $row['id'] == $params['selected']) {
            $li_classes[] = 'selected';
        }
        if (isset($params['collapsible_class']) && !empty($row['childs'])) {
            $li_classes[] = $params['collapsible_class'];
        }
        $html .= '<li'.($li_classes ? ' class="'.implode(' ', $li_classes).'"' : '').'>';
        $elem = $params['elem'];
        foreach ($match[1] as $k) {
            if (strpos($k, '.')) {
                $kp = explode('.', $k);
                $elem = str_replace(':'.$k, isset($row[$kp[0]][$kp[1]]) ? $row[$kp[0]][$kp[1]] : '', $elem);
            } else {
                $elem = str_replace(':'.$k, isset($row[$k]) ? $row[$k] : '', $elem);
            }
        }
        $html .= $elem;
        if (!empty($row['childs'])) {
            $expanded = isset($params['expanded']) && in_array($row['id'], $params['expanded']);
            if (!isset($params['depth']) || $params['depth'] > 0 || $expanded) {
                if (isset($params['depth']) && !$expanded) {
                    $params['depth']--;
                }
                $params['tree'] = $row['childs'];
                $html .= smarty_function_wa_print_tree($params, $smarty);
                if (isset($params['depth']) && !$expanded) {
                    $params['depth']++;
                }
            }
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}