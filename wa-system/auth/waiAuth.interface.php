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
    /**
     * Authorization some entity (for example contact) in current session or some sort of other live-time execution context
     *
     * @param mixed $params = null
     * @throws waAuthException
     * @throws waAuthInvalidCredentialsException
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waException
     */
    public function auth();

    /**
     * Check if in current session or some sort of execution live-time context authorized some entity (for example contact)
     * Returns boolean or some sort of information
     * @throws waAuthException
     * @throws waException
     * @return bool|mixed
     */
    public function isAuth();

    /**
     * Clear current authorization, right after this method called isAuth() method should return FALSE (or some sort of emptiness)
     * @return mixed
     */
    public function clearAuth();

    /**
     * Check if current authorization information is actual (correct, consistent)
     *  for this entity (represented by $data)
     * @param null|array $data
     * @return bool
     */
    public function checkAuth($data = null);

    /**
     * Update current authorization information
     * @param $data
     * @return mixed
     */
    public function updateAuth($data);

    /**
     * Find singed up entity (for example, contact) by authentication ID (token, login or other likewise things)
     * @param $login
     * @return mixed
     */
    public function getByLogin($login);
}
