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
 * @subpackage auth
 */
interface waiAuth
{
    public function auth();
    public function isAuth();
    public function clearAuth();
    public function checkAuth($data = null);
    public function updateAuth($data);
    public function getByLogin($login);
}
