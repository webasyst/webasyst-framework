<?php

function smarty_function_wa_pagination($params, &$smarty)
{

    $total = $params['total'];
    $page = isset($params['page']) ? $params['page'] : waRequest::get('page', 1);
    if ($page < 1) {
        $page = 1;
    }
    $nb = isset($params['nb']) ? $params['nb'] : 1;
    $prev = isset($params['prev']) ? $params['prev'] : '←';
    $next = isset($params['next']) ? $params['next'] : '→';

    if ($total < 2) {
        return '';
    }

    $url = isset($params['url']) ? $params['url'] : wa()->getConfig()->getRequestUrl(false, true);

    $html = '<ul';
    $attrs = isset($params['attrs']) ? $params['attrs'] : array();
    foreach ($attrs as  $k => $v) {
        $html .= ' '.$k.'="'.$v.'"';
    }
    $html .= '>';
    $get_params = waRequest::get();
    if (isset($get_params['page'])) {
        unset($get_params['page']);
    }
    $url_params = http_build_query($get_params);
    if ($page > 1 && $prev) {
        $page_url = $url.($url && $page == 2 ? ($url_params ? '?'.$url_params : '') : '?page='.($page - 1).($url_params ? '&'.$url_params : ''));
        $html .= '<li><a class="inline-link" href="'.$page_url.'">'.$prev.'</a></li>';
    }
    $p = 1;
    $n = 1;
    while ($p <= $total) {
        if ($p > $nb && ($total - $p) > $nb && abs($page - $p) > $n && ($p < $page ? ($page - $n - $p > 1) : ($total - $nb > $p))) {
            $p = $p < $page ? $page - $n : $total - $nb + 1;
            $html .= '<li><span>...</span></li>';
        } else {
            $page_url = $url.($url && $p == 1 ? ($url_params ? '?'.$url_params : '') : '?page='.$p.($url_params ? '&'.$url_params : ''));
            $html .= '<li'.($p == $page ? ' class="selected"' : '').'><a href="'.$page_url.'">'.$p.'</a></li>';
            $p++;
        }
    }
    if ($page < $total && $next) {
        $page_url = $url.'?page='.($page + 1).($url_params ? '&'.$url_params : '');
        $html .= '<li><a class="inline-link" href="'.$page_url.'">'.$next.'</a></li>';
    }
    $html .= '</ul>';
    return $html;
}