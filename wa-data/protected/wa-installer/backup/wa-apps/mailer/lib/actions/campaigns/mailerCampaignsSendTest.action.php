<?php

/**
 * Send a test message from a draft to a small number of recipients.
 */
class mailerCampaignsSendTestAction extends waViewAction
{
    public function execute()
    {
        $campaign_id = waRequest::post('id', 0, 'int');
        if (!$campaign_id) {
            throw new waException('No campaign id given.', 404);
        }

        // Campaign data
        $mm = new mailerMessageModel();
        $campaign = $mm->getById($campaign_id);
        if (!$campaign || ($campaign['status'] > mailerMessageModel::STATUS_DRAFT && $campaign['status'] != mailerMessageModel::STATUS_PENDING) ) {
            throw new waException('Campaign not found.', 404);
        }

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 2) {
            throw new waException('Access denied.', 403);
        }

        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign['id']);

        // Addresses to send message to
        $addresses = array();
        if (waRequest::post('send_to_self')) {
            $addresses[wa()->getUser()->get('email', 'default')] = wa()->getUser()->getName();
        }
        foreach (wao(new mailerMailAddressParser(waRequest::post('addresses', '')))->parse() as $address) {
            if (!isset($addresses[$address['email']])) {
                $addresses[$address['email']] = $address['name'];
                if (count($addresses) >= 10) {
                    break;
                }
            }
        }

        // Optional custom subject for test message
        $subject = waRequest::post('subject', null);

        $errors = self::eventValidateTest($campaign, $params, $addresses, $subject);
        $result = array();
        if (!$errors) {
            $result = wao(new mailerMessage($campaign_id))->sendTestMessage($addresses, $subject);
            self::eventTestSent($campaign, $params, $addresses, $result);
        }

        $this->view->assign('result', $result);
        $this->view->assign('errors', $errors);
    }

    /** Allows plugins to validate campaign before sending. */
    public static function eventValidateTest($campaign, $params, &$addresses, &$subject)
    {
        /**@/**
         * @event campaign.validate_test
         *
         * Allows to validate and cancel sending tests
         *
         * @param array[string]array $params['campaign'] input: row from mailer_message
         * @param array[string]array $params['params'] input: campaign params from mailer_message_params, key => value
         * @param array[string]array $params['addresses'] input/output: test message recipients
         * @param array[string]array $params['subject'] input/output: test message subject
         * @param array[string]array $params['errors'] output: list of error message strings to show to user
         * @return void
         */
        $evt_params = array(
            'campaign' => $campaign,    // INPUT
            'params' => $params,        // INPUT
            'addresses' => &$addresses, // INPUT/OUTPUT
            'subject' => &$subject,     // INPUT/OUTPUT
            'errors' => array(),        // OUTPUT
        );
        wa()->event('campaign.validate_test', $evt_params);
        return (array) $evt_params['errors'];
    }

    public static function eventTestSent($campaign, $params, $addresses, $result)
    {
        /**@/**
         * @event campaign.sending_test
         *
         * Notify plugins about test sending
         *
         * @param array[string]array $params['campaign'] input: row from mailer_message
         * @param array[string]array $params['params'] input: campaign params from mailer_message_params, key => value
         * @param array[string]array $params['addresses'] input: test message recipients
         * @param array[string]array $params['result'] input: sending result: email => error message or ''
         * @param array[string]int $params['sent_count'] input: number of emails successfully sent during test
         * @return void
         */
        $evt_params = array(
            'campaign' => $campaign,     // INPUT
            'params' => $params,         // INPUT
            'addresses' => $addresses,   // INPUT
            'result' => $result,         // INPUT
            'sent_count' => count($result) - count(array_filter($result)), // INPUT
        );
        wa()->event('campaign.sending_test', $evt_params);
    }
}

