<?php

/**
 * List of unsubscribed emails
 */
class mailerUnsubscribedListAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        // POST parameters
        $search = waRequest::request('search');
        $start  = waRequest::request('start', 0, 'int');
        $limit  = waRequest::request('records', 30, 'int');;
        $order  = waRequest::request('order');
        if (!in_array($order, array('datetime', 'email', '!datetime', '!email'))) {
            $order = 'email';
        }

        // Fetch data
        $um = new mailerUnsubscriberModel();
        $list = $um->getListView($search, $start, $limit, $order);
        $total_rows = $um->countListView($search);

        // Format time
        foreach($list as &$row) {
            $row['datetime_formatted'] = mailerCampaignsArchiveAction::formatListDate($row['datetime']);
        }

        // Prepare pagination for template
        mailerHelper::assignPagination($this->view, $start, $limit, $total_rows);

        $this->view->assign('list', $list);
        $this->view->assign('order', $order);
        $this->view->assign('records', $limit);
        $this->view->assign('start', $start);
        $this->view->assign('search_url_append', $search ? $search.'/' : '');
        $this->view->assign('search', $search);
    }
}

