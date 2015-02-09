<?php
/**
 * Continue sending a campain when its status is already set to mailerMessageModel::STATUS_SENDING
 * See mailerCampaignsSettingsAction for previous steps.
 */
class mailerCampaignsSendController extends waJsonController
{
    public function execute()
    {
        $this->getStorage()->close();
        // set max execution time
        set_time_limit(0);
        // ignore user abort (closing of the browser or tab)
        ignore_user_abort(1);

        // send message
        try {
            $id = waRequest::post('id', 0, 'int');
            if (!$id) {
                throw new waException('Bad parameters', 404);
            }

            // Access control
            $mm = new mailerMessageModel();
            if (! ( $campaign = $mm->getById($id))) {
                throw new waException('Not found', 404);
            }
            if ($campaign['status'] != mailerMessageModel::STATUS_SENDING) {
                return;
            }
            if (mailerHelper::campaignAccess($campaign) < 2) {
                throw new waException('Access denied.', 403);
            }

            wao(new mailerMessage($campaign))->send();

            $this->logAction('sent_campaign');

        } catch (Exception $e) {
            $this->errors = $e->getMessage();
        }
    }
}

