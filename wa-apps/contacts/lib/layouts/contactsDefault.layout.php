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
        
        /**
         * Include plugins js and css
         * @event backend_assets
         * @return array[string]string $return[%plugin_id%]
         */
        $this->view->assign('backend_assets', wa()->event('backend_assets'));
        
        /**
         * Include plugins js templates
         * @event backend_tempaltes
         * @return array[string]string $return[%plugin_id%]
         */
        $this->view->assign('backend_templates', wa()->event('backend_templates'));
        
        $this->view->assign(array(
            'admin' => wa()->getUser()->getRights('contacts', 'backend') > 1,
            'global_admin' => wa()->getUser()->getRights('webasyst', 'backend') > 0,
            'fields' => $fields,
            'groups' => $this->getGroups(),
            'paginator_type' => wa('contacts')->getConfig('contacts')->getOption('paginator_type'),
            'lang' => substr(wa()->getLocale(), 0, 2)
        ));
    }
    
    public function getGroups()
    {
        $m = new waGroupModel();
        $groups = $m->getAll();
        foreach ($groups as &$g) {
            $g['name'] = htmlspecialchars($g['name']);
        }
        unset($g);
        return $groups;
    }
}

// EOF