<?php

class mailerCampaignsRecipientsBlockContactsProSearchAction extends waViewAction
{
    public function execute()
    {
        $data = array();
        foreach($this->params['selected'] as $id => $hash) {
            $cc = new waContactsCollection($hash);
            $count = $cc->count();
            $title = $cc->getTitle();
            $data[] = array(
                'label' => $title,
                'list_id' => $id,
                'value' => $hash,
                'checked' => true,
                'disabled' => false,
                'num' => $count,
            );
        }
        $this->view->assign('prosearch_data', $data);
        $this->view->assign('all_selected_id', $this->params['all_selected_id']);
    }
}

