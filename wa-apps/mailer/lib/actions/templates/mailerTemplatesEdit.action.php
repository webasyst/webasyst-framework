<?php

/**
 * Template editor.
 */
class mailerTemplatesEditAction extends mailerTemplatesAddAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        $id = waRequest::get('id');
        $template_model = new mailerTemplateModel();

        $template = $template_model->getById($id);
        if (!$template || !$template['is_template']) {
            throw new waException("Template not found", 404);
        }
        $template['image'] = mailerHelper::getTemplatePreviewUrl($id);
        $this->view->assign('t', $template);

        $params_model = new mailerMessageParamsModel();
        $this->view->assign('params', $params_model->getByMessage($id));

        $message_recipients_model = new mailerMessageRecipientsModel();
        $this->view->assign('to', $message_recipients_model->getByField('message_id', $id, true));

        $this->prepare();

        // Delete old temporary logo file, if exists
        $file = new mailerUploadedFile('template_preview');
        $file->delete();

        $this->view->assign('creator', new waContact($template['create_contact_id']));
    }
}
