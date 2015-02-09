<?php

/**
 * List of templates.
 */
class mailerTemplatesAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        $template_model = new mailerTemplateModel();
        $templates = $template_model->getTemplates();

        foreach($templates as &$v) {
            if (!trim($v['subject'])) {
                $v['subject'] = _w('<no subject>');
            }
            $v['image'] = mailerHelper::getTemplatePreviewUrl($v['id']);
            if (!$v['image']) {
                $v['preview_content'] = preg_replace('~.*<body[^>]*>~si', '', $v['body']);
                $v['preview_content'] = preg_replace('~</body.*~si', '', $v['preview_content']);
            }
        }
        unset($v);

        $this->view->assign('templates', $templates);
    }
}