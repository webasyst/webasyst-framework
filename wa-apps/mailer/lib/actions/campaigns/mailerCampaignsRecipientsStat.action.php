<?php

/**
 *
 */
class mailerCampaignsRecipientsStatAction extends waViewAction
{
    public function execute()
    {
        $campaign_id = waRequest::request('campaign_id', 0, 'int');
        if (!$campaign_id) {
            throw new waException('No campaign id given.', 404);
        }

        // Campaign data
        $mm = new mailerMessageModel();
        $campaign = $mm->getById($campaign_id);
        if (!$campaign) {
            throw new waException('Campaign not found.', 404);
        }

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 2) {
            throw new waException('Access denied.', 403);
        }

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Campaign recipients
        $mrm = new mailerMessageRecipientsModel();
        $recipients = $mrm->getByMessage($campaign_id); // id => value

        // Total number of unique recipients in all lists
        $total_recipients = mailerHelper::countUniqueRecipients($campaign, $params, $recipients, $errormsg);
        $form_disabled = false;
        $recipients_stats = null;
        if ($errormsg) {
            if (!empty($params['recipients_update_progress'])) {
                $form_disabled = true;
                $errormsg = '';
            }
        } else if ($total_recipients <= 5000) {
            $drm = new mailerDraftRecipientsModel();
            $recipients_stats = $drm->getStatsByMessage($campaign['id']);
        }

        // Count all contacts
        $sql = "SELECT COUNT(*) FROM wa_contact";
        $contacts_count = $mrm->query($sql)->fetchField();

        mailerHelper::assignCampaignSidebarVars($this->view, $campaign, $params, $recipients);


        $this->view->assign('errormsg', $errormsg);
        $this->view->assign('form_disabled', $form_disabled);
        $this->view->assign('contacts_count', $contacts_count);
        $this->view->assign('total_recipients', $total_recipients);
        $this->view->assign('recipients_stats', $recipients_stats);
        $this->view->assign('campaign', $campaign);
        $this->view->assign('params', $params);
    }

}

