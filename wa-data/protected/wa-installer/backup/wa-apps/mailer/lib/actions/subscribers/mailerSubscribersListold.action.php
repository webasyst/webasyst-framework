<?php

/**
 * List of subscribers.
 */
class mailerSubscribersListoldAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        // POST parameters
        $search = waRequest::request('search');
        $start  = waRequest::request('start', 0, 'int');
        $limit  = 50;
        $order  = waRequest::request('order');
        if (!in_array($order, array('name', 'datetime', 'email', '!name', '!datetime', '!email'))) {
            $order = 'name';
        }

        // Fetch data
        $sm = new mailerSubscriberModel();
        $list = $sm->getListView($search, $start, $limit, $order);
        $total_rows = $sm->countListView($search);

        // Prepare pagination for template
        mailerHelper::assignPagination($this->view, $start, $limit, $total_rows);

        $this->view->assign('list', $list);
        $this->view->assign('order', $order);
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

