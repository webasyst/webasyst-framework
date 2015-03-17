<?php

/**
 * Available as {$wa->mailer} helper in smarty.
 */
class mailerViewHelper
{
    public function getConfigOption($opt)
    {
        return wa('mailer')->getConfig()->getOption($opt);
    }

    public function isAdmin()
    {
        return mailerHelper::isAdmin();
    }

    public function isAuthor()
    {
        return mailerHelper::isAuthor();
    }

    public function isInspector()
    {
        return mailerHelper::isInspector();
    }

    public function writable($campaign)
    {
        return mailerHelper::campaignAccess($campaign) >= 2;
    }

    public function form($form_id)
    {
        $mf = new mailerFormModel();
        $mailer_form = $mf->getById($form_id);
        if (!$mailer_form) {
            return false;
        }

        $old_app = wa()->getApp();
        wa('mailer', true);

        $uniqid = 'mailer'.md5(serialize($mf->getById($form_id)));

        $html = mailerHelper::generateHTML($form_id, $uniqid);

        $view = wa()->getView();
        $view->assign('uniqid', $uniqid );
        $js = $view->fetch(wa()->getAppPath('templates/forms/subscription_form_inner_script.html'));

        wa($old_app, true);

        return $html.$js;
    }
}

