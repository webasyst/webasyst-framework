<?php

/**
 * List of recipients for campaign draft.
 */
class mailerCampaignsRecipientsPreviewAction extends waViewAction
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

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 2) {
            throw new waException('Access denied.', 403);
        }

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Campaign recipients
        if ($campaign['status'] > 0 && $campaign['status'] != mailerMessageModel::STATUS_PENDING) {
            throw new waException('Recipients list preview is only available for drafts.');
        }

        // Recipients list
        $limit = 50;
        $start = waRequest::request('start', 0, 'int');
        $type = waRequest::request('type', '');
        $recipients = $this->getRecipientsDraft($campaign, $params, $type, $start, $limit);

        $parameters = 'start='.($start + $limit);

        $this->view->assign('start', $start);
        $this->view->assign('limit', $limit);
        $this->view->assign('parameters', $parameters);
        $this->view->assign('recipients', $recipients);
        $this->view->assign('campaign', $campaign);
        $this->view->assign('params', $params);
    }

    protected function getRecipientsDraft($campaign, $params, $type, $start, $limit)
    {
        $start = (int) $start;
        if (! ( $limit = (int) $limit)) {
            $limit = 50;
        }

        $drm = new mailerDraftRecipientsModel();
        $total = mailerHelper::countUniqueRecipients($campaign);
        if ($start >= $total) {
            $this->view->assign('has_more', false);
            return array();
        }
        $this->view->assign('has_more', $total > $start + $limit);

        switch ($type) {
            case 'unsubscribed':
                $sql = "SELECT dr.*
                        FROM mailer_draft_recipients AS dr
                            JOIN mailer_unsubscriber AS u
                                ON dr.email = u.email
                        WHERE dr.message_id=?
                        GROUP BY dr.email
                        ORDER BY dr.name, dr.email
                        LIMIT $start, $limit";
                break;
            case 'unavailable':
                $sql = "SELECT dr.*
                        FROM mailer_draft_recipients AS dr
                            JOIN wa_contact_emails AS e
                                ON dr.email = e.email
                        WHERE dr.message_id=?
                            AND e.status='unavailable'
                        GROUP BY dr.email
                        ORDER BY dr.name, dr.email
                        LIMIT $start, $limit";
                break;
            default:
                $sql = "SELECT *
                        FROM mailer_draft_recipients
                        WHERE message_id=?
                        GROUP BY email
                        ORDER BY name, email
                        LIMIT $start, $limit";
                break;
        }
        $result = $drm->query($sql, $campaign['id'])->fetchAll();

        if (count($result) < $limit) {
            $this->view->assign('has_more', false);
        }
        return $result;
    }
}

