<?php

/**
 * Content block for recipients selection form.
 * Implements dialog to enter a list of emails.
 */
class mailerCampaignsRecipientsBlockFlatListAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('remove_ids', $this->params['ids']);
        $this->view->assign('data', $this->params['all_emails']);
    }
}

