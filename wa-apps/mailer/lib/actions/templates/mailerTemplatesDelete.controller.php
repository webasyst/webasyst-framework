<?php

/**
 * Delete template.
 */
class mailerTemplatesDeleteController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        $id = waRequest::post('id', 0, 'int');
        if ($id) {
            $template_model = new mailerTemplateModel();
            $template_model->deleteById($id);
        }
    }
}