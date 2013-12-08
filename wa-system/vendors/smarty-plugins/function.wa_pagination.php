<?php
/**
 * Smarty plugin to generate a list of pagination links
 * 
 * @param array $params Array of parameters. Possible values are:
 *  'total' - Total pages. Default 1
 *  'nb' - how many numbers to include on either side of the current page. Default 1
 *  'prev' - Title for 'Previous' link. Default is left arrow
 *  'next' - Title for 'Next' link. Default is right arrow
 *  'url' - URL. Default empty (current)
 *  'attrs' - array of attributes of ul tag. Empty by default
 *  'page' - Current page number. Set to 0 to extract automatically from GET parameter 'page'
 *  'hide_disabled_li' - Show/hide disabled list items for prev/next links at first and last page
 *  'attrs_disabled_li' - Attributes of disabled list tag if it shown
 *  'attrs_link' - Link tag attributes
 *  'attrs_li' - Normal list tag attributes
 *  'attrs_current_li'
 * @param type $smarty
 * @return string
 * 
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 */
function smarty_function_wa_pagination($params, &$smarty)
{
    $defaults = array(
        'total' => 1,
        'nb' => 1,
        'prev' => '&larr;',
        'next' => '&rarr',
        'url' => '',
        'attrs' => array(),
        'page' => 0,
        'hide_disabled_li' => TRUE,
        'attrs_disabled_li' => array(),
        'attrs_link' => array('class'=>'inline-link'),
        'attrs_li' => array(),
        'attrs_current_li' => array()
    );

    $params = array_merge($defaults, $params);

    extract($params);
    
    $html = '';
    
    if ($total < 2) {
        return $html;
    }

    $page = $page ?: waRequest::get('page', 1);
    if ($page < 1) {
        $page = 1;
    }

    $html .= '<ul';
    foreach ($attrs as  $k => $v) {
        $html .= ' '.$k.'="'.$v.'"';
    }
    $html .= '>';

    $get_params = waRequest::get();
    if (isset($get_params['page'])) {
        unset($get_params['page']);
    }

    $link_attr_str = '';
    foreach ($attrs_link as $k => $v) {
        $link_attr_str .= " $k=\"$v\"";
    }

    $li_disabled_attr_str = '';
    foreach ($attrs_disabled_li as $k => $v) {
        $li_disabled_attr_str .= " $k=\"$v\"";
    }

    $li_attr_str = '';
    foreach ($attrs_li as $k => $v) {
        $li_attr_str .= " $k=\"$v\"";
    }

    $li_current_attr_str = '';
    foreach ($attrs_current_li as $k => $v) {
        $li_current_attr_str .= " $k=\"$v\"";
    }

    /**
     * Make a 'Prev' link
     */
    if(!$hide_disabled_li || ($page > 1 && $prev)) {

        $url_params_array = $get_params;

        // First page doesn't need the 'page' parameter
        if($page > 2) {
            $url_params_array['page'] = $page - 1;
        }

        $url_params = http_build_query($url_params_array);

        $page_url = $url . ($url_params ? "?$url_params" : '');

        if($page > 1) {
            $html .= "<li{$li_attr_str}><a{$link_attr_str} href=\"{$page_url}\">{$prev}</a></li>";
        } else {
            $html .= "<li{$li_disabled_attr_str}>{$prev}</li>";
        }        
    }

    /**
     * Make links to pages
     */
    $p = 1;
    $n = 1;
    while ($p <= $total) {
        if ($p > $nb && ($total - $p) > $nb && abs($page - $p) > $n && ($p < $page ? ($page - $n - $p > 1) : ($total - $nb > $p))) {
            $p = $p < $page ? $page - $n : $total - $nb + 1;
            $html .= "<li$li_disabled_attr_str>&hellip;</li>";
        } else {
            if($p == 1) {
                unset($url_params_array['page']);
            } else {
                $url_params_array['page'] = $p;
            }

            $url_params = http_build_query($url_params_array);

            $page_url = $url.($url_params ? "?$url_params" : '');
            if($p == $page) {
                $html .= "<li{$li_current_attr_str}><a{$link_attr_str} href=\"$page_url\">$p</a></li>";
            } else {
                $html .= "<li{$li_attr_str}><a{$link_attr_str} href=\"$page_url\">$p</a></li>";
            }
            $p++;
        }
    }

    /**
     * Make a 'Next' link
     */
    if (!$hide_disabled_li || ($page < $total && $next)) {

        $url_params_array['page'] = $page;
        if($page < $total) {
            $url_params_array['page']++;
        }
        $url_params = http_build_query($url_params_array);

        $page_url = $url.($url_params ? "?$url_params" : '');

        if($total > $page) {
            $html .= "<li{$li_attr_str}><a{$link_attr_str} href=\"$page_url\">$next</a></li>";
        } else {
            $html .= "<li{$li_disabled_attr_str}><a{$link_attr_str} href=\"$page_url\">$next</a></li>";
        }
    }

    $html .= '</ul>';

    return $html;
}