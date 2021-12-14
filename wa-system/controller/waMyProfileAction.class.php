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

    /**
     * @var string waContactForm namesapce
     */
    protected $namespace = 'profile';

    public function execute()
    {
        $this->form = $this->getForm();
        $this->contact = $this->getContact();

        if (waRequest::post()) {
            $saved = $this->saveFromPost($this->form, $this->contact);
            if ($saved) {
                wa()->getStorage()->set('my/profile/updated', true);
                $this->redirect(wa()->getConfig()->getRequestUrl(false, true));
            }
        }

        // here is updated contact
        $this->form->setValue($this->contact);

        $this->view->assign('saved', boolval(wa()->getStorage()->getOnce('my/profile/updated')));
        $this->view->assign('contact', $this->contact);
        $this->view->assign('form', $this->form);
        $this->view->assign('user_info', $this->getFormFieldsHtml());
    }

    /**
     * @param waContactForm $form
     * @param waContact $contact
     * @return bool
     * @throws waException
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

                $filename = $path . $rand . ".original.jpg";
                waFiles::create($filename);
                waImage::factory($photo_file)->save($filename, 90);

                $filename = $path . $rand . ".jpg";
                waFiles::create($filename);
                waImage::factory($photo_file)->crop($square, $square)->save($filename, 90);

                waContactFields::getStorage('waContactInfoStorage')->set($contact, array('photo' => $rand));

            } elseif (empty($data['photo'])) { // remove photo
                $contact->set('photo', "");
            }

            // just in case, may be some outer code user values array
            $this->form->values['photo'] = $contact->get('photo');

            // after saving page it is not reloaded, but waContactForm gets data from post property to render itself by html() method
            $this->form->post['photo'] = $contact->get('photo');
        }

        if (isset($data['phone'])) {
            $this->preparePhonesBeforeSave($data['phone'], $contact->getId());
        }

        $post = $form->post;
        $form->post = $data;

        // Validation
        if (!$form->isValid($contact)) {
            return false;
        }

        $form->post = $post;

        // Password validation
        if (!empty($data['password']) && $data['password'] !== $data['password_confirm']) {
            $form->errors('password', _ws('Passwords do not match'));
            return false;
        } elseif (!empty($data['password']) && strlen($data['password']) > waAuth::PASSWORD_MAX_LENGTH) {
            $form->errors('password', _ws('Specified password is too long.'));
            return false;
        } elseif (empty($data['password']) || empty($data['password_confirm'])) {
            unset($data['password']);
        }
        unset($data['password_confirm']);

        // get old data for logging
        $old_data = [];
        if ($this->contact) {
            foreach ($data as $field_id => $field_value) {
                $old_data[$field_id] = $this->contact->get($field_id);
            }
        }

        if (isset($data['address'])) {
            $this->prepareAddressesBeforeSave($data['address']);
        }

        foreach ($data as $field => $value) {
            // except photo, photo is already set
            if ($field != 'photo') {
                $contact->set($field, $value);
            }
        }
        $errors = $contact->save();

        // If something went wrong during save for any reason,
        // show it to user. In theory it shouldn't but better be safe.
        if ($errors) {
            foreach ($errors as $field => $errs) {
                foreach ($errs as $e) {
                    $form->errors($field, $e);
                }
            }
            return false;
        }

        // get new data for logging
        $new_data = array();
        foreach ($data as $field_id => $field_value) {
            $new_data[$field_id] = $this->contact->get($field_id);
        }

        $this->logProfileEdit($old_data, $new_data);

        return true;
    }

    /**
     * Prepare phones before save, with take into account phone transformation and without reset existing statuses
     * @param array $phones
     * @param $contact_id
     */
    protected function preparePhonesBeforeSave(&$phones, $contact_id)
    {
        $phones = is_scalar($phones) ? (array)$phones : $phones;
        if (!is_array($phones)) {
            return;
        }

        $query = (new waContactDataModel())->select('value, status')
            ->where("contact_id = :contact_id AND field = 'phone'", ['contact_id' => $contact_id])
            ->order('sort')
            ->query();

        $map = [];
        foreach ($query as $item) {
            $result = $this->transformPhone($item['value']);
            $phone = $result['phone'];
            if (!isset($map[$phone])) {
                $map[$phone] = $item['status'];
            }
        }

        foreach ($phones as &$phone) {
            if (is_array($phone)) {
                $value = $phone['value'];
                $ext = $phone['ext'];
            } else {
                $value = $phone;
                $ext = '';
            }

            $result = $this->transformPhone($value);
            $result['phone'] = waContactPhoneField::cleanPhoneNumber($result['phone']);

            $status = waContactDataModel::STATUS_UNKNOWN;
            if (isset($map[$result['phone']])) {
                $status = $map[$result['phone']];
            }

            $phone = [
                'value' => $result['phone'],
                'status' => $status,
                'ext' => $ext
            ];
        }
        unset($phone);

        foreach ($phones as $idx => $phone) {
            if (!$phone['value']) {
                unset($phones[$idx]);
            }
        }

        $phones = array_values($phones);
    }

    protected function prepareAddressesBeforeSave(&$address_data)
    {
        if (!is_array($address_data)) {
            return;
        }

        // This is a list of all addresses saved in contact. [ i => array( data => array, ext => string ) ]
        $contact_addresses = $this->contact['address'];

        // preserve address 'ext'
        if (!isset($address_data[0])) {
            $address_data = array($address_data);
        }

        foreach ($address_data as $index => &$address) {

            if (isset($address['data']) && (isset($address['ext']) || isset($address['value']))) {
                $address = $address['data'];
            }

            if (isset($contact_addresses[$index])) {
                $ext = isset($contact_addresses[$index]['ext']) ? $contact_addresses[$index]['ext'] : null;
            } else {
                $ext = null;
            }

            $address = array(
                'value' => $address,
                'ext' => $ext
            );
        }
        unset($address);
    }

    protected function transformPhone($phone)
    {
        if ($this->isValidPhoneNumber($phone)) {
            // non-international phone try to convert to international
            $is_international = substr($phone, 0, 1) === '+';
            if (!$is_international) {
                return waDomainAuthConfig::factory()->transformPhone($phone);
            }
        }
        return [
            'status' => false,
            'phone' => $phone
        ];
    }

    protected function isValidPhoneNumber($phone)
    {
        return (new waPhoneNumberValidator())->isValid($phone);
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
            foreach(array('firstname', 'middlename', 'lastname', 'email', 'phone', 'password', 'password_confirm') as $fld_id) {
                if (!empty($fields[$fld_id])) {
                    $enabled[$fld_id] = $fields[$fld_id];
                }
            }
        }

        return waContactForm::loadConfig($enabled, array(
            'namespace' => $this->namespace
        ));
    }

    /**
     * @description Get HTML with contact info (field name => field html)
     * @return array
     * @throws waException
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
            $result = $this->getFormFieldHtml($field);
            if ($result) {
                $user_info[$id] = $result;
            }
        }
        return $user_info;
    }

    /**
     * @param waContactField $field
     * @return array $result - if return EMPTY array that this field not passed in template as part of user info
     *      string          $result['name'] - formatted field name
     *      string|string[] $result['value'] - formatted field value(s)
     * @throws waException
     */
    protected function getFormFieldHtml($field)
    {
        $id = $field->getId();
        if (!in_array($id, array('password', 'password_confirm'))) {
            if ($id === 'photo') {
                return array(
                    'name' => _ws('Photo'),
                    'value' => '<img src="'.$this->contact->getPhoto().'">',
                );
            } else {
                return array(
                    'name' => $field->getName(null, true),
                    'value' => $this->contact->get($id, 'html'),
                );
            }
        }
        return [];
    }

    public function display($clear_assign = true)
    {
        return parent::display(false);
    }
}

