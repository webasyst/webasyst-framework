<?php

$common_route_rules = array(
    'category/<category>/' => array(
        'module' => 'frontend',
        'search' => 'category',
    )
);

$differ_route_rules = array(
    0 => array(),
    1 => array(),
    2 => array(),
);

if ($_url_type > 0) {
    foreach ($differ_route_rules as $blog_type_url => &$routes) {
        if ($blog_type_url == 0) {
            $url_pattern = '<blog_url>/:URL:/<post_url>/';
        } else {
            $url_pattern = ':URL:/<post_url>/';
        }
        foreach ($_all_categories as $category) {
            $url = str_replace(':URL:', $category['url'], $url_pattern);
            $routes[$url] = 'frontend/post';
        }
    }
    unset($routes);
}

$route_rules = array();
foreach ($differ_route_rules as $blog_type_url => $routes) {
    $route_rules[$blog_type_url] = array_merge($common_route_rules, $routes);
}

return $route_rules;
