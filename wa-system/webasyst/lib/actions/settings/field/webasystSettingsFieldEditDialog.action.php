<?php

class webasystSettingsFieldEditDialogAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $field_constructor = new webasystFieldConstructor();
        $field = $field_constructor->getFieldInfo($this->getId());

        $cf = waContactFields::get($this->getId());

        $this->view->assign(array(
            'locale'        => $field_constructor->getLocale(),
            'other_locales' => $field_constructor->getOtherLocales(),
            'field_types'   => $field_constructor->getFieldTypes(),
            'field'         => $field,
            'cf'            => $cf,
        ));
    }

    protected function getId()
    {
        return $this->getRequest()->request('id', '', waRequest::TYPE_STRING_TRIM);
    }
}