<?php

class mailerCampaignsReportGraphDataController extends waJsonController
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
        $lm = new mailerMessageLogModel();
        $interval_start = waRequest::get('intervalstart', 0, 'int');
        $interval_end = waRequest::get('intervalend', 0, 'int');
        $status = waRequest::get('status', 0);
        $quantum = waRequest::get('quantum', 0, 'int');
        $graphs = false;
        if ($status) {
            $graphs = $lm->getGraphData($campaign_id, $status, $interval_start, $interval_end, $quantum);
        }
        $this->response = $graphs;
    }
} 