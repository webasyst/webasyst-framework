<?php

class webasystSettingsFieldDeleteConfirmAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $id = $this->getId();
        if (strlen($id) <= 0) {
            throw new waException('Field not found');
        }

        $hash = "/search/{$id}!=";
        $collection = new waContactsCollection($hash);
        $count = $collection->count();
        $field = waContactFields::get($id, 'all');
        $this->view->assign(array(
            'id'    => $id,
            'name'  => $field->getName(null, true),
            'count' => $count
        ));
    }

    protected function getId()
    {
        return trim((string) $this->getRequest()->request('id'));
    }
}