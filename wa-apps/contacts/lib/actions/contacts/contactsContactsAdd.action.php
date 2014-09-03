<?php
/** Create new contact form. */
class contactsContactsAddAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getRights('create')) {
            throw new waRightsException('Access denied.');
        }

        $type = waRequest::get('company') ? 'company' : 'person';
        $fields = array();
        foreach (waContactFields::getAll($type, true) as $field_id => $field) {
            $fields[$field_id] = $field->getInfo();
            $fields[$field_id]['top'] = $field->getParameter('top');
        }
        
        $this->view->assign('contactFields', $fields);
        $this->view->assign('contactType', $type);
    }
}

// EOF
