<?php

/**
 * Content block for recipients selection form.
 * Shows checklist to select subscribers.
 */
class mailerCampaignsRecipientsBlockSubscribersAction extends waViewAction
{
    public function execute()
    {
        $mlm = new mailerSubscribeListModel();
        $lists = $mlm->getAllListsList();

        $sm = new mailerSubscriberModel();
        $subscribers_count = $sm->countListView('');

        $data = array(
            0 => array(
            'label' => _w('All subscribers'),
            'value' => 0,
            'checked' => false,
            'disabled' => false,
            'num' => $subscribers_count,
            )
        );
        foreach($lists as $id => $list) {
            $data[$list['list_id']] = array(
                'label' => $list['list_name'],
                'value' => $list['list_id'],
                'checked' => false,
                'disabled' => false,
                'num' => $list['subscribers'],
            );
        }

        foreach($this->params['selected'] as $id => $list_id) {
            $data[$list_id]['checked'] =  true;
            $data[$list_id]['list_id'] = $id;
        }

        $this->view->assign('contacts_count', $sm->countAll());
        $this->view->assign('data', $data);
        $this->view->assign('all_selected_id', $this->params['all_selected_id']);
    }
}

