<?php

class webasystSettingsFieldAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $field_constructor = new webasystFieldConstructor();

        $this->view->assign(array(
            'fields'        => $field_constructor->getAllFields(),
            'locale'        => $field_constructor->getLocale(),
            'other_locales' => $field_constructor->getOtherLocales(),
            'field_types'   => $field_constructor->getFieldTypes(),
        ));
    }
}