<?php
/*
 * Controller to add or edit subscription form
 */
class mailerSubscribersFormeditorAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $form_id = waRequest::get('id',0,'int');

        $msl = new mailerSubscribeListModel();
        $all_lists_list = $msl->getAllListsList();

        $this->view->assign('all_lists_list', $all_lists_list);

        if ($form_id > 0) {
            $mf = new mailerFormModel();
            $form = $mf->getById($form_id);

            $this->view->assign('uniqid', 'mailer'.md5(serialize($form)));

            $mfsl = new mailerFormSubscribeListsModel();
            $form['lists'] = $mfsl->getListsIds($form_id);

            $mfp = new mailerFormParamsModel();
            $form['params'] = $mfp->get($form_id);
        }
        else{
            $form = array();
        }
        $this->view->assign('form', $form);

        $this->view->assign('confirmation_template_vars', $this->getConfirmationVars());
    }

    protected function getConfirmationVars()
    {
        return array(
            '{SUBSCRIBER_NAME}' => _w("Subscriber name, if it used in the form.."),
            '{SUBSCRIPTION_CONFIRM_URL}' => _w("URL address to confirm subscription. Click on this link opens \"My subscriptions\" page in the client's Customer Portal.")
        );
    }
}