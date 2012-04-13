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
        $fields = waContactFields::getInfo($type, TRUE);
        $this->view->assign('contactFields', $fields);
        $this->view->assign('contactType', $type);
        $this->view->assign('header', _w('New '.($this->getConfig()->getInfo('edition') === 'full' ? $type : 'contact')));
        $this->view->assign('limitedCategories', $this->getRights('category.all') ? 0 : 1);
    }
}

// EOF
