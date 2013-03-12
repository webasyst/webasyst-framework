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
            $url_params = preg_replace('/&?page=[0-9]+?/i', '', waRequest::server('QUERY_STRING', ''));
            if (substr($url_params, -1) == '&') {
                $url_params = substr($url_params, 0, -1);
            }
            $page_url = $url.($url && $p == 1 ? ($url_params ? '?'.$url_params : '') : '?page='.$p.($url_params ? '&'.$url_params : ''));
            $html .= '<li'.($p == $page ? ' class="selected"' : '').'><a href="'.$page_url.'">'.$p.'</a></li>';
            $p++;
        }
    }
    $html .= '</ul>';
    return $html;
}