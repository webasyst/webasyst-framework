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
class waContactEmailListFormatter extends waContactFieldFormatter
{
	public function format($data)
	{
		if (is_array($data)) {
			$data['data'] = $data['value'];
		} else {
			$data = array(
				'data' => $data,
				'value' => $data
			);
		}
		if (!$data['data']) {
			$data['value'] = '';
			return $data;
		}
		$href = 'mailto:'.$data['data'];
		$data['value'] = '<a href="'.addslashes($href).'">'.htmlspecialchars($data['value']).'</a>';
		return $data;
	}
}