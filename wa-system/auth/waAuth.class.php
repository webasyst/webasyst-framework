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
class waAuth implements waiAuth
{	
	protected $options = array(
		'cookie_expire' => 2592000
	);
	
	public function __construct($options = array())
	{
		if (is_array($options)) {
			foreach ($options as $k => $v) {
				$this->options[$k] = $v;
			}
		}
	}	
	
	/**
	 * Auth user returns result of auth
	 * 
	 * @param waiAuthAdapter $auth_adapter
	 * @return mixed
	 */
	public function auth($params = array())
	{
		$result = $this->_auth($params);
		if ($result !== false) {
			waSystem::getInstance()->getStorage()->write('auth_user', $result);
			waSystem::getInstance()->getUser()->init();
		}
		return $result;
	}
	
	public function isAuth()
	{
		return waSystem::getInstance()->getStorage()->read('auth_user');
	}
	
	protected function _auth($params)
	{
		$model = new waContactModel();
		if ($params && isset($params['login']) && isset($params['password'])) {
			$login = $params['login'];
			$password = $params['password'];
		}
		if (waRequest::getMethod() == 'post' && !waRequest::isXMLHttpRequest()) {
			if (strlen(waRequest::post('login', '')) > 0) {
				$user_info = $model->getByField('login', waRequest::post('login'));
				if ($user_info && $user_info['is_user'] &&
					waSystem::getInstance()->getUser()->getPasswordHash(waRequest::post('password')) === 
					$user_info['password']) {
					$response = waSystem::getInstance()->getResponse();
					// if remember
					if (waRequest::post('remember')) {
	                    $response->setCookie('auth_token', $this->getToken($user_info), time() + 2592000);
	                    $response->setCookie('remember', 1);
					} else {
						$response->setCookie('remember', null, -1);
					}	
					
					// return array with compact user info 
					return array(
						'id' => $user_info['id'], 
						'login' => $user_info['login']
					);
				} else {
					throw new waException(_ws('Invalid login or password'));	
				}
			} else {
				throw new waException(_ws('Login is required'));
			}
		} elseif (waSystem::getSetting('rememberme', 1, 'webasyst') && $token = waRequest::cookie('auth_token')) {
			$response = waSystem::getInstance()->getResponse();
			$id = substr($token, 15, -15);
			$user_info = $model->getById($id);
			if ($user_info && $user_info['is_user'] && 
				$token === $this->getToken($user_info)) {
				$response->setCookie('auth_token', $token, time() + 2592000);
				return array(
					'id' => $user_info['id'], 
					'login' => $user_info['login']
				);
			} else {
				$response->setCookie('auth_token', null, -1);
			}
		}
		return false;
	}	
	
	public function getToken($user_info)
	{
		$hash = md5($user_info['login'] . $user_info['password']);
		return substr($hash, 0, 15).$user_info['id'].substr($hash, -15);
	}	
	
	/**
	 * Clear all auth tokens in storage and cookies
	 * 
	 * @return void
	 */
	public function clearAuth()
	{
		waSystem::getInstance()->getStorage()->destroy();
		if (waRequest::cookie('auth_token')) {
			waSystem::getInstance()->getResponse()->setCookie('auth_token', null, -1);
		}
	}
	
	public function getOptions()
	{
		return $this->options;	
	}
}
