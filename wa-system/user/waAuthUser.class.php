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
 * @subpackage user
 */
class waAuthUser extends waUser
{
    /**
     * @var waStorage
     */
    protected $storage;
    protected $auth = null;
        
    public function __construct($id = null, $options = array())
    {
        foreach ($options as $name => $value) {
            self::$options[$name] = $value;
        }        
        $this->init();
    }
    
    
    public function init()
    {
        parent::init();
        if (waSystem::getInstance()->getEnv() != 'frontend' || waRequest::param('session', true)) {
	        $this->storage = waSystem::getInstance()->getStorage();
	        if (!isset(self::$options['session_timeout'])) {
	            self::$options['session_timeout'] = 1800;
	        }
	        
	        if (ini_get('session.gc_maxlifetime') < self::$options['session_timeout']) {
	            ini_set('session.gc_maxlifetime', self::$options['session_timeout']);
	        }        
	            
	        $auth = waSystem::getInstance()->getAuth();
	        $info = $auth->isAuth();
	        if (!$info && !waRequest::post('wa_auth_login')) {
	        	$info = $auth->auth();
	        }
	        if ($info && isset($info['id'])) {
	            $this->auth = true;
	            $this->id = $info['id'];
	            if (!waRequest::request('background_process')) {
	            	$this->updateLastTime();
	            }
	        }
        }
    }
    
    public function updateLastPage() {
        if (waRequest::isXMLHttpRequest() || !$this->id || wa()->getEnv() !== 'backend') {
            return;
        }
        $page = wa()->getRequest()->server('REQUEST_URI');
        $backend = wa()->getConfig()->getBackendUrl(true);
        if ($page === $backend || substr($page, 0, strlen($backend)+1) === $backend.'?') {
            return;
        }
        wa()->getResponse()->setCookie('last_page', $this->getId().'^^^'.$page);
    }
    
    public function getLastPage() {
        if (! ( $page = wa()->getRequest()->cookie('last_page'))) {
            return '';
        }
        
        $page = explode('^^^', $page, 2);
        if(!is_array($page) || !isset($page[1]) || $page[0] != $this->getId()) {
            wa()->getResponse()->setCookie('last_page', '');
            return '';
        }
        return $page[1];
    }
    
    public function updateLastTime($force = false)
    {
        $time = $this->storage->read('user_last_datetime'); 
        if (!$time || $force || $time == '0000-00-00 00:00:00' || 
             (time() - strtotime($time) > 120)
        ) {
        	try {
            	$login_log_model = new waLoginLogModel();
            	$last_activity = $login_log_model->getCurrent($this->id);
        	} catch (waDbException $e) {
        		if ($e->getCode() == 1146) {
        			waSystem::getInstance()->getAuth()->clearAuth();
        			header("Location: ".wa()->getConfig()->getBackendUrl(true));
        			exit;
        		}
        	}
        	$contact_model = new waContactModel();
        	$contact_info = $contact_model->getById($this->id);
        	if (!$contact_info || !$contact_info['is_user']) {
        		waSystem::getInstance()->getAuth()->clearAuth();
        		header("Location: ".wa()->getConfig()->getBackendUrl(true));
        		exit;
        	} else {
        		$this->setCache($contact_info);
        	}
            if (!$last_activity) {
                $login_log_model->insert(array(
                    'contact_id' => $this->id,
                    'datetime_in' => date("Y-m-d H:i:s"),
                    'datetime_out' => null
                ));                
            } elseif ($last_datetime = strtotime($time)) {
                if (time() - $last_datetime > self::$options['activity_timeout']) {
                    $login_log_model->updateById($last_activity['id'], array('datetime_out' => $time));
                    $login_log_model->insert(array(
                        'contact_id' => $this->id,
                        'datetime_in' => date("Y-m-d H:i:s"),
                        'datetime_out' => null
                    ));                
                }
            } 
            $t = date("Y-m-d H:i:s");
            $contact_model->updateById($this->id, array('last_datetime' => $t));
            $this->storage->write('user_last_datetime', $t);
        } 
    }
    
    public function setLocale($locale)
    {
        $this->info['locale'] = $locale;
    }
    
    public function save($data = array(), $validate = false)
    {
        if ($result = parent::save()) {
        	if ($this->storage) {
            	$this->storage->write('user_info', $this->info);
        	}
        }
        return $result;
    }
                    
    public function isAuth()
    {
        return (bool)$this->auth;
    }
    
    public function getPasswordHash($password)
    {
        return md5($password);
    }
    
    public function checkChangePassword()
    {
        $change_password = $this->getSettings('webasyst', 'change_password');
        if ($change_password) {
            $a = explode('|', $change_password);
            $change_password = array();
            foreach ($a as $part) {
                if (strpos($part, ':') === false) {
                    $change_password[$part] = true;
                } else {
                    $part = explode(':', $part, 2);
                    $change_password[$part[0]] = $part[1];
                }
            }
            
            if (isset($change_password['next']) && $change_password['next']) {
                return true;
            }
            
            if (isset($change_password['date']) && $change_password['date'] <= date("Y-m-d")) {
                return true;
            }
            
            if (isset($change_password['auth']) && $change_password['auto']) {
                $last_change_password = $this->getSettings('webasyst', 'last_change_password');
                switch ($change_password['auto']) {
                    case 'day':
                        return time() - strtotime($last_change_password) > 24 * 3600;
                    case 'week':
                        
                }
            }
        }
        return false;
    }
}

// EOF