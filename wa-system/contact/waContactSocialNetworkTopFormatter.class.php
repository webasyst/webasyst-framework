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
class waContactSocialNetworkTopFormatter extends waContactFieldFormatter
{
    public static function formatLink($data)
    {
        $value = $data['value'];
        if (!preg_match("/^(http|https):/", $value)) {
            $f = waContactFields::get('socialnetwork');
            if ($f) {
                $domain = $f->getParameter('domain');
                if (!empty($domain[$data['ext']])) {
                    $d = $domain[$data['ext']];
                    if (strpos($value, $d) === false) {
                        $value = '<a href="'.'http://'.$d.'/'.ltrim($value, "/ ").'" target="_blank">'.$value.'</a>';
                    }
                }
            }
        } else {
            $value = "<a href='{$value}' target='_blank'>{$value}</a>";
        }
        return $value;
    }

    public function format($data)
    {
        $data['value'] = htmlspecialchars(trim($data['value']));
        $value = self::formatLink($data);

        $icon = '';
        $ext = '';
        if (isset($data['ext']) && $data['ext'] && ( $f = waContactFields::get('socialnetwork'))) {
            $exts = $f->getParameter('ext');
            if (isset($exts[$data['ext']])) {
                $ext = ' <em class="hint">'.$exts[$data['ext']].'</em>';
                $icon = '<i class="icon16 '.$data['ext'].'"></i>';
            } else {
                $ext = ' <em class="hint">'.htmlspecialchars($data['ext']).'</em>';
            }
        }

        return $icon.$value.$ext;
    }
}