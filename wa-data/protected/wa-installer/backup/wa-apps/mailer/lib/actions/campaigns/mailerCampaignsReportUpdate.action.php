<?php

/**
 * Returns HTML/JS to update report page for campaign currently being sent.
 */
class mailerCampaignsReportUpdateAction extends mailerCampaignsReportAction
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
        if ($campaign['status'] <= 0) {
            throw new waException('Unable to show report for a message draft.', 404);
        }
        if ($campaign['status'] == mailerMessageModel::STATUS_PENDING) {
            throw new waException('Unable to show report for a pending message.', 404);
        }
            // Access control
        if (mailerHelper::campaignAccess($campaign) < 1) {
            throw new waException('Access denied.', 403);
        }

        $campaign['opened_count_available'] = mailerCampaignsArchiveAction::isOpenedCountAvailable($campaign);
        $campaign['has_unsubscribe_link'] = mailerCampaignsArchiveAction::hasUnsubscribeLink($campaign);

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Recipients stats for pie graph
        $stats = $this->getStats($campaign_id);

        // Assign $campaign['duration'] and $campaign['estimated_finish_datetime']
        $this->updateCampaignTimes($campaign, $params, $stats);

        $this->view->assign('is_sending', wao(new mailerMessage($campaign_id))->isSending());
        $this->view->assign('campaign', $campaign);
        $this->view->assign('params', $params);
        $this->view->assign('stats', $stats);
    }
}

