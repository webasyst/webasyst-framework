<?php

/**
 * Initial controller for subscribers
 */
class mailerSubscribersAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $list_id = waRequest::get('id', 0, 'int');
        $form_id = waRequest::get('form_id', 0, 'int');
        $order = waRequest::get('order','name');
        $start = waRequest::get('start',0,'int');
        $records  = waRequest::get('records', 30, 'int');
        $search = waRequest::get('search','');
        if (!in_array($order, array('name', 'datetime', 'email', '!name', '!datetime', '!email'))) {
            $order = 'name';
        }

        $mlm = new mailerSubscribeListModel();
        $all_lists_list = $mlm->getAllListsList();

        $sm = new mailerSubscriberModel();
        $subscribers_count = $sm->countListView('');

        $mf = new mailerFormModel();
        $forms_list = $mf->getAll('id');

        $this->view->assign('all_lists_list', $all_lists_list);
        $this->view->assign('list_id', $list_id);
        $this->view->assign('search', $search);
        $this->view->assign('start', $start);
        $this->view->assign('records', $records);
        $this->view->assign('order', $order);
        $this->view->assign('form_id', $form_id);
        $this->view->assign('forms_list', $forms_list);
        $this->view->assign('subscribers_count', $subscribers_count);
    }
}