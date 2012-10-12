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
class waContactIMTopFormatter extends waContactFieldFormatter
{
    public function format($data)
    {
        $value = htmlspecialchars($data['value']);

        $icon = '';
        $ext = '';        
        if (isset($data['ext']) && $data['ext'] && ( $f = waContactFields::get('im'))) {
            $exts = $f->getParameter('ext');
            if (isset($exts[$data['ext']])) {
                $ext = ' <em class="hint">'.$exts[$data['ext']].'</em>';
                $icon = '<i class="icon16 '.$data['ext'].'"></i>';
            } else {
                $ext = ' <em class="hint">'.htmlspecialchars($data['ext']).'</em>';
            }
        }
        
        if (!$icon) {
            $icon = '<i class="icon16 im"></i>';
        }
        
        return $icon.$value.$ext;
    }
}