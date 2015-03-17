<?php

/** Save data from categories editor form */
class contactsCategoriesSaveController extends waJsonController
{
    public function execute() {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException(_w('Access denied'));
        }

        $cm = new waContactCategoryModel();
        $id = waRequest::post('id');
        $name = waRequest::post('name', 'string');

        if (!$id) {
            if (!$name && $name !== '0') {
                throw new waException('No id and no name given.');
            }
            $id = $cm->add($name);
            $this->logAction('category_add', $id);
        } else if ($name || $name === '0') {
            $cm->updateById($id, array('name' => $name));
        }

        $this->response['id'] = $id;
    }
}

// EOF