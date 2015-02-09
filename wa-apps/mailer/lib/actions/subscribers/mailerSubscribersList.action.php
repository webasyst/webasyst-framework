<?php

/**
 * List of subscribers.
 */
class mailerSubscribersListAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $list_id = waRequest::get('id', 0, 'int');
        $search = waRequest::get('search', '');
        $start  = waRequest::get('start', 0, 'int');
        $records  = waRequest::get('records', 30, 'int');
        $order  = waRequest::get('order');
        if (!in_array($order, array('name', 'datetime', 'email', '!name', '!datetime', '!email'))) {
            $order = 'name';
        }

        $mf = new mailerFormModel();
        $all_forms = $mf->getAll('id');

        $subscribe_list = false;
        if ($list_id > -1) {
            // Fetch data
            $sm = new mailerSubscriberModel();
            $list = $sm->getListView($search, $start, $records, $order, $list_id);
            $total_rows = $sm->countListView($search, $list_id);
            $subscribers_count = $sm->countListView('');

            $sml = new mailerSubscribeListModel();
            $subscribe_list = $sml->getListById($list_id);

            $mfsl = new mailerFormSubscribeListsModel();
            $subscribe_list['forms'] = $mfsl->getForms($list_id);
            foreach($subscribe_list['forms'] as $form) {
                $all_forms[$form['id']]['checked'] = true;
            }

            // Prepare pagination for template
            mailerHelper::assignPagination($this->view, $start, $records, $total_rows);

            $this->view->assign('list', $list);
            $this->view->assign('total_rows', $total_rows);
            $this->view->assign('subscribers_count', $subscribers_count);
        }

        $this->view->assign('list_id', $list_id);
        $this->view->assign('all_forms', $all_forms);
        $this->view->assign('subscribe_list', $subscribe_list);
        $this->view->assign('order', $order);
        $this->view->assign('start', $start);
        $this->view->assign('records', $records);
        $this->view->assign('search_url_append', $search ? $search.'/' : '');
        $this->view->assign('form_html', $this->getFormHtml());
        $this->view->assign('search', $search);
        $this->view->assign('cols', array(
            'name' => _w('Name'),
            'email' => _w('Email'),
            'datetime' => _w('Subscribe date'),
        ));
    }

    protected function getFormHtml()
    {
        $view = wa()->getView();
        return $view->fetch('templates/actions/subscribers/basic_form.html');
    }
}

