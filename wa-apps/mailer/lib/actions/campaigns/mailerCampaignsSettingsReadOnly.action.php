<?php

/**
 * For campaign sent or being sent, show its parameters.
 */
class mailerCampaignsSettingsReadOnlyAction extends waViewAction
{
    public function execute()
    {
        $campaign_id = waRequest::get('campaign_id', 0, 'int');
        if (!$campaign_id) {
            throw new waException('No campaign id given.', 404);
        }

        // Campaign data
        $mm = new mailerMessageModel();
        $campaign = $mm->getById($campaign_id);
        if (!$campaign) {
            throw new waException('Campaign not found.', 404);
        }
        if ($campaign['status'] <= 0 || $campaign['status'] == mailerMessageModel::STATUS_PENDING) {
            echo "<script>window.location.hash = '#/campaigns/send/{$campaign_id}/';</script>";
            exit;
        }

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 1) {
            throw new waException('Access denied.', 403);
        }

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        $ms = new mailerSenderModel();
        $sender = $ms->getById($campaign['sender_id']);

        $msp = new mailerSenderParamsModel();
        $sender_params = $msp->getBySender($sender['id']);

        // if we save sender_params in campaigns_params table
        if (isset($params['sender_params'])) {
            $sender_params = unserialize($params['sender_params']);
        }

        $mrp = new mailerReturnPathModel();
        $return_path = $mrp->getByField('email', $campaign['return_path']);

        mailerHelper::assignCampaignSidebarVars($this->view, $campaign, $params);
        $this->view->assign('campaign', $campaign);
        $this->view->assign('params', $params);
        $this->view->assign('sender', $sender);
        $this->view->assign('sender_params', $sender_params);
        $this->view->assign('return_path', $return_path);
    }
}

