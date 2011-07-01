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
class waContactEmailTopFormatter extends waContactFieldFormatter
{
	public function format($data) {
	    if (is_array($data)) {
            $result = htmlspecialchars($data['value']);
            $result = '<a class="inline" href="mailto:'.$result.'">'.$result.'</a>';
            if (isset($data['ext']) && $data['ext']) {
                $ext = $data['ext'];
                $f = waContactFields::get('email');
                $exts = $f->getParameter('ext');
                if (isset($exts[$ext])) {
                    $ext = _ws($exts[$ext]);
                } 
                $result .= ' <em class="hint">'.htmlspecialchars($ext).'</em>';
            }
            return $result;
	    }
        return htmlspecialchars($data);
	}
}