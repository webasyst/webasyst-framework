<?php

class mailerFrontendFormAction extends waViewAction
{
    public function execute()
    {
        $form_id = waRequest::param('id');
        $mf = new mailerFormModel();
        $mailer_form = $mf->getById($form_id);
        if (!$mailer_form) {
            throw new waException('Page not found', 404);
        }

        $uniqid = 'mailer'.md5(serialize($mf->getById($form_id)));

        $html = mailerHelper::generateHTML($form_id, $uniqid);

        $view = wa()->getView();
        $view->assign('uniqid', $uniqid );
        $js = $view->fetch(wa()->getAppPath('templates/forms/subscription_form_inner_script.html'));

        $this->view->assign('styles_and_form', $html);
        $this->view->assign('js', $js );
    }
}