<?php

class webasystSettingsFieldDeleteController extends webasystSettingsJsonController
{
    public function execute()
    {
        $id = $this->getId();
        if (strlen($id) <= 0) {
            return $this->errors[] = 'Field not found.';
        }

        $constructor = new webasystFieldConstructor();
        if ($constructor->isFieldSystem($id)) {
            return $this->errors[] = 'Unable to delete protected system field.';
        }
        $constructor->deleteField($id);

        $this->response = array(
            'done' => true
        );
    }

    protected function getId()
    {
        return trim((string) $this->getRequest()->request('id'));
    }
}