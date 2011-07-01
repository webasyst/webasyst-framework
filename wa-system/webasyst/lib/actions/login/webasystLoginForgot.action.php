<?php 

class webasystLoginForgotAction extends waViewAction
{
	public function execute()
	{
		if (waRequest::method() == 'post') {
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
					$url = wa()->getRootUrl(true).$this->getConfig()->getBackendUrl().'/?action=password&key='.$hash;
					$this->view->assign('url', $url);
					// send email
					$subject = _w("Recovering password");
					$body = $this->view->fetch('templates/mail/RecoveringPassword.html');
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
		$this->view->assign('backend_url', $this->getConfig()->getBackendUrl(true));
		
		$app_settings_model = new waAppSettingsModel();
        $background = $app_settings_model->get('webasyst', 'auth_form_background');
        $stretch = $app_settings_model->get('webasyst', 'auth_form_background_stretch');
        if ($background) {
            $background = 'wa-data/public/webasyst/'.$background;
        }
        $this->view->assign('stretch', $stretch);         
        $this->view->assign('background', $background);		
	}
}