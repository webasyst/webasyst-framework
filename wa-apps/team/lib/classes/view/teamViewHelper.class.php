<?php

class teamViewHelper
{
    protected static $url = '';

    /**
     * @param $count
     * @param $page
     * @param $url_params
     * @param int $limit
     * @return string
     */
    public function pager($count, $page, $url_params = '', $limit = teamConfig::ROWS_PER_PAGE)
    {
        $width = 5;
        $html = '';
        $page = max($page, 1);
        self::$url = '?page=';
        $url_params = trim(trim($url_params), '&?');
        $total = 0;
        if (isset($count['folders']) && isset($count['files']) &&
            is_numeric($count['folders']) &&
            is_numeric($count['files'])
        ) {
            $total = intval($count['folders']) + intval($count['files']);
        } elseif (is_numeric($count)) {
            $total = $count;
        }
        if ($total) {
            $pages = ceil($total / $limit);
            if ($pages > 1) {
                $page = intval($page);
                $html = '<ul class="pager">';
                if (is_numeric($count)) {
                    $html .= '<li>'._w('Total:').' <em>'.number_format((float)$count, 0, '.', ' ').'</em></li>';
                }
                if (!empty($count['folders'])) {
                    $html .= '<li>'._w('Folders:').' <em>'.number_format((float)$count['folders'], 0, '.', ' ').'</em></li>';
                }
                if (!empty($count['files'])) {
                    $html .= '<li>'._w('Files:').' <em>'.number_format((float)$count['files'], 0, '.', ' ').'</em></li>';
                }

                $html .= ' <span>'._w('Page:').'</span></li>';

                if ($page > 1) {
                    $title = _w('prev');
                    $url = self::$url . ($page - 1) . (strlen($url_params) > 0 ? '&' . $url_params : '');
                    $html .= "<li><a href='{$url}' title='{$title}'><i class='icon10 larr'></i>{$title}</a></li>";
                }

                $html .= self::item(1, $page, $url_params);
                for ($i = 2; $i < $pages; $i++) {
                    if (abs($page - $i) < $width ||
                        ($page - $i == $width && $i == 2) ||
                        ($i - $page == $width && $i == $pages - 1)
                    ) {
                        $html .= self::item($i, $page, $url_params);
                    } elseif (strpos(strrev($html), '...') != 5) { // 5 = strlen('</li>')
                        $html .= '<li>...</li>';
                    }
                }

                $html .= self::item($pages, $page, $url_params);

                if ($page < $pages) {
                    $title = _w('next');
                    $url = self::$url . ($page + 1) . (strlen($url_params) > 0 ? '&' . $url_params : '');
                    $html .= "<li><a href='{$url}' title='{$title}'>{$title}<i class='icon10 rarr'></i></a></li>";
                }
            }
        }
        return $html;
    }

    /**
     * @param $i
     * @param $page
     * @param $url_params
     * @return string
     */
    protected static function item($i, $page, $url_params = '')
    {
        if ($page != $i) {
            $url = self::$url . $i . (strlen($url_params) > 0 ? '&' . $url_params : '');
            return "<li><a href='{$url}'>".number_format((float)$i, 0, '.', ' ')."</a></li>";
        } else {
            return "<li class='selected'>".number_format((float)$i, 0, '.', ' ')."</li>";
        }
    }
}