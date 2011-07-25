<?php 

abstract class waLoginAction extends waViewAction
{
    
    
    public function getTitle()
    {
        return wa()->getSetting('name', 'Webasyst', 'webasyst');
    }
    
    public function execute()
    {
        $title = $this->getTitle();
        
        if (waRequest::get('forgot')) {
            $title .= ' - '._ws('Password recovery');
            if (waRequest::method() == 'post') {
                $this->forgot();
            }
            $this->view->assign('type', 'forgot');
        } elseif (waRequest::get('password') && waRequest::get('key')) {
            $this->recovery();
            $this->view->assign('type', 'password');
        } else {
            $this->view->assign('type', '');
        }
        
        $this->view->assign('title', $title);
        
        $app_settings_model = new waAppSettingsModel();
        $background = $app_settings_model->get('webasyst', 'auth_form_background');
        $stretch = $app_settings_model->get('webasyst', 'auth_form_background_stretch');
        if ($background) {
            $background = 'wa-data/public/webasyst/'.$background;
        }
        $this->view->assign('stretch', $stretch);
        $this->view->assign('background', $background);

        $this->view->assign('remember_enabled', $app_settings_model->get('webasyst', 'rememberme', 1));

        $auth = $this->getAuth();
        try {
        	if ($auth->auth()) {
        	    $this->afterAuth();
        	}
        } catch (waException $e) {
        	$this->view->assign('error', $e->getMessage());
        }
        
       	$this->view->assign('options', $auth->getOptions());        
        
        if ($this->template === null) {
            if (waRequest::isMobile()) {
                $this->template = 'LoginMobile.html';
            } else {
                $this->template = 'Login.html';
            }
            $this->template = wa()->getAppPath('templates/actions/login/', 'webasyst').$this->template;
        }
    }
    
    /**
     * @return waAuth
     */
    protected function getAuth()
    {
        return waSystem::getInstance()->getAuth();
    }
    
    
    protected function forgot()
    {
		$login = waRequest::post('login');
		$contact_model = new waContactModel();
		if (strpos($login, '@')) {
			$sql = "SELECT c.* FROM wa_contact c JOIN wa_contact_emails e ON c.id = e.contact_id
			WHERE c.is_user = 1 AND e.email LIKE s:email ORDER BY c.last_datetime DESC LIMIT 1";
			$contact_info = $contact_model->query($sql, array('email' => $login))->fetch();
			$this->view->assign('email', true);
		} else {
			$contact_info = $contact_model->getByField('login', $login);
		}
		// if contact found and it is user
		if ($contact_info && $contact_info['is_user']) {
			$contact = new waContact($contact_info['id']);
			$contact->setCache($contact_info);
			// get defaul email to send mail
			if ($to = $contact->get('email', 'default')) {
				// generate unique key and save in contact settings
				$hash = md5(uniqid(null, true));
				$contact_settings_model = new waContactSettingsModel();
				$contact_settings_model->set($contact['id'], 'webasyst', 'forgot_password_hash', $hash);
				$hash = substr($hash, 0, 16).$contact['id'].substr($hash, -16);
				// url to recovery password
				if ($this->getApp() === 'webasyst') { 
				    $url = wa()->getAppUrl().'?password=1&key='.$hash;
				} else {
				    $url = $this->getConfig()->getCurrentUrl();
				    $url = preg_replace('/\?.*$/i', '', $url);
				    $url .= '?password=1&key='.$hash;
				}
				$this->view->assign('url', $this->getConfig()->getHostUrl().$url);
				// send email
				$subject = _w("Recovering password");
				if (file_exists(wa()->getAppPath('templates/mail/RecoveringPassword.html'))) {
				    $body = $this->view->fetch('templates/mail/RecoveringPassword.html');
				} else {
				    $body = $this->view->fetch(wa()->getAppPath('templates/mail/RecoveringPassword.html', 'webasyst'));
				}
				$this->view->clearAllAssign();
				$mailer = new waMail();
				if ($mailer->send($to, $subject, $body)) {
					$this->view->assign('success', true);							
				} else {
					$this->view->assign('error', _w('Sorry, we can not recover password for this login name or email. Please refer to your system administrator.'));	
				}
			} else {
				$this->view->assign('error', _w('Sorry, we can not recover password for this login name or email. Please refer to your system administrator.'));	
			}				
		} else {
			$this->view->assign('error', _w('No user with this login name or email has been found.'));
		}
    }
    
    protected function recovery()
    {
		$hash = waRequest::get('key');
		$error = true;
		if ($hash && strlen($hash) > 32) {
			$contact_id = substr($hash, 16, -16);
			$contact_settings_model = new waContactSettingsModel();
			$contact_hash = $contact_settings_model->getOne($contact_id, 'webasyst', 'forgot_password_hash');
			$contact_hash = substr($contact_hash, 0, 16).$contact_id.substr($contact_hash, -16);
			$contact_model = new waContactModel();
			$contact_info = $contact = $contact_model->getById($contact_id);
			if ($contact_info && $hash === $contact_hash) {
			    $auth = $this->getAuth();
			    if ($auth->getOption('login') == 'login') {
			        $login = $contact_info['login'];
			    } elseif ($auth->getOption('login') == 'email') {
			        $email_model = new waContactEmailsModel();
			        $email = $email_model->getByField(array('contact_id' => $contact_id, 'sort' => 0));
			        $login = $email['email'];
			    }
				$this->view->assign('login', $login);
				if (waRequest::method() == 'post') {
					$password = waRequest::post('password');
					$password_confirm = waRequest::post('password_confirm');
					if ($password === $password_confirm) {
						$user = new waUser($contact_id);
						$user['password'] = $password;
						$user->save();
						$contact_settings_model->delete($contact_id, 'webasyst', 'forgot_password_hash');
						// auth
						$this->getAuth()->auth(array(
						    'login' => $login,
						    'password' => $password
						));						
						$this->redirect(wa()->getAppUrl());
					} else {
						$this->view->assign('error', _w('Passwords do not match'));
					}
				}
				$error = false;
			}
		}
		if ($error) {
			$this->redirect($this->getConfig()->getBackendUrl(true));
		}        
    }
    
    abstract protected function afterAuth();
}