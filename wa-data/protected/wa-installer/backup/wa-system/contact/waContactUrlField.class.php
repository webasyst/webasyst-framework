<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage contact
 */
class waContactUrlField extends waContactStringField
{
    public function init()
    {
        if (!isset($this->options['formats']['js'])) {
            $this->options['formats']['html'] = new waContactUrlHtmlFormatter();
            $this->options['formats']['js'] = new waContactUrlJsFormatter();
        }
        if (!isset($this->options['validators'])) {
            $this->options['validators'] = new waUrlValidator($this->options);
        }
    }

    protected function setValue($value)
    {
        if (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
        }
        $value = (string)$value;
        if ($value && !strpos($value, '://')) {
            $value = 'http://'.$value;
        }
        return $value;
    }
}

class waContactUrlJsFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        if (is_array($data)) {
            $data['data'] = $data['value'];
        } else {
            $data = array(
                'data' => $data,
            );
        }
        if (!$data['data']) {
            $data['value'] = '';
            return $data;
        }
        $href = $data['data'];
        if (strpos($href, '://') === false) {
            $href = 'http://'.$href;
        }
        $name = substr($href, strpos($href, '://') + 3);
        $data['value'] = '<a target="_blank" href="'.addslashes($href).'">'.htmlspecialchars($name).'</a><a target="_blank" href="'.addslashes($href).'"><i class="icon16 new-window"></i></a>';
        if ($url = @parse_url($href)) {

            if (isset($url['host']) && in_array($url['host'], array('facebook.com', 'twitter.com', 'vkontakte.ru'))) {
                $data['value'] = '<a target="_blank" href="'.addslashes($href).'"><i class="icon16" style="background-image: url(http://'.$url['host'].'/favicon.ico)"></i>'.htmlspecialchars($name).'<i class="icon16 new-window"></i></a>';
            }
        }
        return $data;
    }
}

class waContactUrlHtmlFormatter extends waContactUrlJsFormatter
{
    public function format($data)
    {
        $data = parent::format($data);
        return $data['value'].' <em class="hint">'.htmlspecialchars(ifset($data['ext'])).'</em>';
    }
}

