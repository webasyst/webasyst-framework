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
class waContactSocialNetworkJSFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        if (is_array($data)) {
            $data['data'] = $data['value'];
            $data['value'] = htmlspecialchars($data['value']);
        } else {
            $data = array(
                'data' => $data,
                'value' => htmlspecialchars($data),
            );
        }
        if (!$data['data']) {
            $data['value'] = '';
            return $data;
        }

        $icon = '';
        if (isset($data['ext']) && $data['ext'] && ( $f = waContactFields::get('socialnetwork'))) {
            $exts = $f->getParameter('ext');
            if (isset($exts[$data['ext']])) {
                $icon = '<i class="icon16 '.$data['ext'].'"></i>';
            }
        }

        $data['value'] = $icon.waContactSocialNetworkTopFormatter::formatLink($data);

        return $data;
    }
}
