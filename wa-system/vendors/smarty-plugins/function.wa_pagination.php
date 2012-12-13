<?php

function smarty_function_wa_pagination($params, &$smarty)
{

    $total = $params['total'];
    $page = isset($params['page']) ? $params['page'] : waRequest::get('page', 1);
    if ($page < 1) {
        $page = 1;
    }

    if ($total < 2) {
        return '';
    }

    $url = isset($params['url']) ? $params['url'] : '';

    $html = '<ul';
    $attrs = isset($params['attrs']) ? $params['attrs'] : array();
    foreach ($attrs as  $k => $v) {
        $html .= ' '.$k.'="'.$v.'"';
    }
    $html .= '>';
    $p = 1;
    $n = 1;
    $nb = 1;
    while ($p <= $total) {
        if ($p > $nb && ($total - $p) > $nb && abs($page - $p) > $n && ($p < $page ? ($page - $n - $p > 1) : ($total - $nb > $p))) {
            $p = $p < $page ? $page - $n : $total - $nb + 1;
            $html .= '<li>...</li>';
        } else {
            $html .= '<li'.($p == $page ? ' class="selected"' : '').'><a href="'.$url.($url && $p == 1 ? '' : '?page='.$p).'">'.$p.'</a></li>';
            $p++;
        }
    }
    $html .= '</ul>';
    return $html;
}