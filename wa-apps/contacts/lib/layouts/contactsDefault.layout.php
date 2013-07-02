<?php

class contactsDefaultLayout extends waLayout
{
    public function execute()
    {
        // Layout caching is forbidden
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Expires: " . date("r"));

        $this->executeAction('sidebar', new contactsBackendSidebarAction());

        $fields = array();
        // normally this is done with waContactFields::getInfo() but we don't need most of the info
        // so we loop through fields manually.
        foreach (waContactFields::getAll('enabled') as $field_id => $f) {
            /**
             * @var $f waContactField
             */
            $fields[$field_id] = array();
            $fields[$field_id]['id'] = $field_id;
            $fields[$field_id]['name'] = $f->getName();
            $fields[$field_id]['fields'] = $f instanceof waContactCompositeField;
            if ( ( $ext = $f->getParameter('ext'))) {
                $fields[$field_id]['ext'] = $ext;
                foreach ($fields[$field_id]['ext'] as &$v) {
                    $v = _ws($v);
                }
            }
        }

        // Plugin assets
        if ($this->getConfig()->getInfo('edition') === 'full') {
            wa()->event('assets');
        }

        $this->view->assign('admin', wa()->getUser()->getRights('contacts', 'backend') > 1);
        $this->view->assign('global_admin', wa()->getUser()->getRights('webasyst', 'backend') > 0);
        $this->view->assign('fields', $fields);
        $this->view->assign('versionFull', $this->getConfig()->getInfo('edition') === 'full');
    }
}

// EOF