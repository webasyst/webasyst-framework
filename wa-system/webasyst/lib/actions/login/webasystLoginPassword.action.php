<?php 

class webasystLoginPasswordAction extends waViewAction
{
	public function execute()
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
				$this->view->assign('login', $contact_info['login']);
				if (waRequest::method() == 'post') {
					$password = waRequest::post('password');
					$password_confirm = waRequest::post('password_confirm');
					if ($password === $password_confirm) {
						$user = new waUser($contact_id);
						$user['password'] = $password;
						$user->save();
						$contact_settings_model->delete($contact_id, 'webasyst', 'forgot_password_hash');
						// auth
						waSystem::getInstance()->getStorage()->write('auth_user', array(
							'id' => $contact_id,
							'login' => $contact_info['login']
						));
						
						$this->redirect($this->getConfig()->getBackendUrl(true));
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