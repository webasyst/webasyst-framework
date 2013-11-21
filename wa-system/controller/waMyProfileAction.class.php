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
    }

    /**
     * @return bool
     */
    protected function saveFromPost($form, $contact)
    {
        // Validation
        if (!$form->isValid($contact)) {
            return false;
        }

        $data = $form->post();
        if (!$data || !is_array($data)) {
            return false;
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

        return true;
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
        $fields = waContactFields::getAll('person');
        $enabled = array();
        foreach($fields as $fld_id => $f) {
            if ($f->getParameter('my_profile')) {
                $enabled[$fld_id] = $f;
            }
        }

        // If nothing found, fall back to the default field list
        if (!$enabled) {
            foreach(array('firstname', 'middlename', 'lastname', 'email', 'phone') as $fld_id) {
                if (!empty($fields[$fld_id])) {
                    $enabled[$fld_id] = $fields[$fld_id];
                }
            }
        }

        return waContactForm::loadConfig($enabled, array(
            'namespace' => 'profile'
        ));
    }
}

