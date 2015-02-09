<?php

/**
 * Empty form to create new template.
 */
class mailerTemplatesAddAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        $this->template = 'TemplatesEdit';
        $this->view->assign('t', array());
        $this->view->assign('params', array());
        $this->view->assign('creator', wa()->getUser());
        $this->prepare();
    }

    protected function prepare()
    {
        $this->view->assign('lang', substr(wa()->getLocale(), 0, 2));
        //$this->view->assign('wysiwyg_off', 1);
    }
}