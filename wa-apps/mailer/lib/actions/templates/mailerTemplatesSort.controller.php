<?php

/**
 * Update template ordering when user drags them around in list view.
 */
class mailerTemplatesSortController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        $values = waRequest::post('values');
        if (!is_array($values)) {
            return;
        }
        $template_model = new mailerTemplateModel();
        $template_model->updateSort($values);
    }
}
