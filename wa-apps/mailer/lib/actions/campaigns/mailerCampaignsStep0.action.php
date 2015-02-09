<?php

/**
 * New campaign page. Suggests a list of templates to choose from.
 */
class mailerCampaignsStep0Action extends waViewAction
{
    public function execute()
    {
        // Sccess control
        if (!mailerHelper::isAuthor()) {
            throw new waException('Access denied.', 403);
        }

        // List of templates
        $tm = new mailerTemplateModel();
        $templates = $tm->getTemplates();

        if (!$templates) {
            // When there are no templates, open next step immidiately,
            // creating new campaign from scratch.
            $this->redirect('?module=campaigns&action=step1');
        }

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

