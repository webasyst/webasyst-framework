<?php
/**
 * Profile editor form for various "my account" sections with front-end auth.
 * To use it, an application must extend this class it and provide a template,
 * similar to waLoginAction.
 */
abstract class waMyProfileAction extends waViewAction
{
    /** @var waContactForm */
    public $form = null;

    /** @var waContact */
    public $contact = null;

    public function execute()
    {
        $this->form = $this->getForm();
        $this->contact = $this->getContact();

        $this->form->setValue($this->contact);

        $saved = waRequest::post() && $this->saveFromPost($this->form, $this->contact);

        $this->view->assign('saved', $saved);
        $this->view->assign('contact', $this->contact);
        $this->view->assign('form', $this->form);
        $this->view->assign('user_info', $this->getFormFieldsHtml());
    }

    /**
     * @return bool
     */
    protected function saveFromPost($form, $contact)
    {
        $data = $form->post();
        if (!$data || !is_array($data)) {
            return false;
        }

        // save photo before all
        $photo_file = waRequest::file('photo_file');
        if (array_key_exists('photo', $data)) {
            if ($photo_file->uploaded() && $avatar = $photo_file->waImage()) { // add/update photo
                $square = min($avatar->height, $avatar->width);

                // setPhoto with crop
                $rand = mt_rand();
                $path = wa()->getDataPath(waContact::getPhotoDir($contact->getId()), true, 'contacts', false);
                // delete old image
                if (file_exists($path)) {
                    waFiles::delete($path);
                }
                waFiles::create($path);

                $filename = $path.$rand.".original.jpg";
                waFiles::create($filename);
                waImage::factory($photo_file)->save($filename, 90);

                $filename = $path.$rand.".jpg";
                waFiles::create($filename);
                waImage::factory($photo_file)->crop($square, $square)->save($filename, 90);

                waContactFields::getStorage('waContactInfoStorage')->set($contact, array('photo' => $rand));
            } elseif (empty($data['photo'])) { // remove photo
                $contact->set('photo', "");
            }
            $this->form->values['photo'] = $data['photo'] = $contact->get('photo');
        }

        // Validation
        if (!$form->isValid($contact)) {
            return false;
        }

        // Password validation
        if (!empty($data['password']) && $data['password'] !== $data['password_confirm']) {
            $form->errors('password', _ws('Passwords do not match'));
            return false;
        } elseif (empty($data['password']) || empty($data['password_confirm'])) {
            unset($data['password']);
        }
        unset($data['password_confirm']);

        // get old data for logging
        if ($this->contact) {
            $old_data = array();
            foreach ($data as $field_id => $field_value) {
                $old_data[$field_id] = $this->contact->get($field_id);
            }
        }

        foreach ($data as $field => $value) {
            $contact->set($field, $value);
        }
        $errors = $contact->save();

        // If something went wrong during save for any reason,
        // show it to user. In theory it shouldn't but better be safe.
        if ($errors) {
            foreach ($errors as $field => $errs) {
                foreach($errs as $e) {
                    $form->errors($field, $e);
                }
            }
            return false;
        }

        // get new data for logging
        $new_data = array();
        foreach ($data as $field_id => $field_value) {
            if (!isset($errors[$field_id])) {
                $new_data[$field_id] = $this->contact->get($field_id);
            }
        }
        
        $this->logProfileEdit($old_data, $new_data);

        return true;
    }

    public function logProfileEdit($old_data, $new_data)
    {
        $diff = array();
        wa_array_diff_r($old_data, $new_data, $diff);
        if (!empty($diff)) {
            $this->logAction('my_profile_edit', $diff, null, $this->contact->getId());
        }
    }
    
    /**
     * waContact to use as initial form data.
     * @return waContact
     */
    protected function getContact()
    {
        return wa()->getUser();
    }

    /**
     * @return waContactForm
     */
    protected function getForm()
    {
        // Read all contact fields and find all enabled for "my profile"
        $fields = array(
                'photo' => new waContactHiddenField('photo', _ws('Photo')),
            ) + waContactFields::getAll('person') + array(
                'password' => new waContactPasswordField('password', _ws('Password')),
            ) + array(
                'password_confirm' => new waContactPasswordField('password_confirm', _ws('Confirm password')),
            );

        $domain = wa()->getRouting()->getDomain();
        $domain_config_path = wa()->getConfig()->getConfigPath('domains/'.$domain.'.php', true, 'site');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        $enabled = array();
        foreach($fields as $fld_id => $f) {
            if (!empty($domain_config['personal_fields'][$fld_id])) {
                $enabled[$fld_id] = $f;
                if ($fld_id === 'password') {
                    $enabled[$fld_id.'_confirm'] = $fields[$fld_id.'_confirm'];
                }
            }
        }

        // If nothing found, fall back to the default field list
        if (!$enabled) {
            foreach(array('firstname', 'middlename', 'lastname', 'email', 'phone', 'password') as $fld_id) {
                if (!empty($fields[$fld_id])) {
                    $enabled[$fld_id] = $fields[$fld_id];
                }
            }
        }

        return waContactForm::loadConfig($enabled, array(
            'namespace' => 'profile'
        ));
    }

    /**
     * @description Get HTML with contact info (field name => field html)
     * @return array
     */
    protected function getFormFieldsHtml()
    {
        if (!$this->contact) {
            $this->contact = $this->getContact();
        }
        if (!$this->form) {
            $this->form = $this->getForm();
        }
        $user_info = array();
        foreach($this->form->fields as $id => $field) {
            if (!in_array($id, array('password', 'password_confirm'))) {
                if ($id === 'photo') {
                    $user_info[$id] = array(
                        'name' => _ws('Photo'),
                        'value' => '<img src="'.$this->contact->getPhoto().'">',
                    );
                } else {
                    $user_info[$id] = array(
                        'name' => $this->form->fields[$id]->getName(null, true),
                        'value' => $this->contact->get($id, 'html'),
                    );
                }
            }
        }
        return $user_info;
    }
}

