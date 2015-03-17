<?php

/**
 * Delete campaign.
 */
class mailerCampaignsDeleteController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id');
        $mm = new mailerMessageModel();
        $campaign = $mm->getById($id);
        if (!$campaign) {
            return;
        }
        if (mailerHelper::campaignAccess($campaign) < 2) {
            throw new waException('Access denied.', 403);
        }

        $mm->delete($id);
        waFiles::delete(wa()->getDataPath('files/'.$id, true, 'mailer'));
    }
}
