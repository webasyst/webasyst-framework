<?php

// TODO: Change extands class
class webasystSettingsFieldSortSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $field_ids = $this->getRequest()->post('fields');
        if (!$field_ids) {
            return;
        }

        $constructor = new webasystFieldConstructor();
        $constructor->saveFieldsOrder($field_ids);

        $this->response = array(
            'done' => true
        );
    }
}