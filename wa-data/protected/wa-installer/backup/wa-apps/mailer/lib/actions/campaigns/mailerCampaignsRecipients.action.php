<?php

/**
 * Campaign editor, recipients.
 * Controller generates HTML form and accepts submit from this form.
 */
class mailerCampaignsRecipientsAction extends waViewAction
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

        $contacts_plugins = wa('contacts')->getConfig()->getPlugins();
        $you_need_contacts = wa()->getUser()->getRights('contacts', 'backend') == 0 ? true : false;

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Campaign recipients
        $mrm = new mailerMessageRecipientsModel();
        $recipients = $mrm->getByMessage($campaign_id); // id => value

        // When user saves recipients, clear last error state to be able to try again
        if (waRequest::post() && !empty($params['recipients_update_error'])) {
            unset($params['recipients_update_error']);
            $mpm->deleteByField(array(
                'message_id' => $campaign_id,
                'name' => 'recipients_update_error',
            ));
        }

        // If POST data came, save to DB and update $recipients
        if ($this->saveRecipientsFromPost($campaign, $recipients)) {
            unset($params['recipients_count'], $params['recipients_update_progress'], $params['recipients_update_error']);
            $mpm->deleteByField(array(
                'message_id' => $campaign_id,
                'name' => array('recipients_count', 'recipients_update_progress', 'recipients_update_error'),
            ));
            $mlm = new mailerMessageLogModel();
            $mlm->deleteByField('message_id', $campaign['id']);
        }

        // Prepare recipients data for template: count every separate list and total number of unique addresses.
        $recipients_groups = self::getRecipientsGroups($campaign, $recipients);

        // Does user have admin access to contacts app?
        $is_contacts_admin = wa()->getUser()->getRights('contacts', 'backend') > 1;

        // Count all contacts
        $sql = "SELECT COUNT(*) FROM wa_contact";
        $contacts_count = $mrm->query($sql)->fetchField();

        mailerHelper::assignCampaignSidebarVars($this->view, $campaign);

        $a = array_flip($recipients);
        $all_contacts_selected_id = ifset($a['/']);

        mailerHelper::updateDraftRecipients($campaign['id'], 'NameAndCountRecipients'); // get names anf count for recipient groups, but don't fill recipientsDraft table

        $this->view->assign('contacts_count', $contacts_count);
        $this->view->assign('is_contacts_admin', $is_contacts_admin);
        $this->view->assign('all_contacts_selected_id', $all_contacts_selected_id);
        $this->view->assign('recipients_groups', $recipients_groups);
        $this->view->assign('recipients', mailerHelper::getRecipients($campaign['id']));
        $this->view->assign('campaign', $campaign);
        $this->view->assign('params', $params);
        $this->view->assign('you_need_contacts', $you_need_contacts);

    }

    protected static function getRecipientsGroups($campaign, $recipients)
    {
        /**@/**
         * @event recipients.form
         *
         * Custom recipients selection criteria: UI
         *
         * This plugin hook is one of two which allow to implement custom recipient
         * selection criteria. This one collects HTML/JS for recipient selection form.
         *
         * Some criteria, such as by contact category or locale, are supported
         * by the core application and not plugins. Nonetheless, they are implemented
         * through the same interface using this event.
         * For mailer core implementation see: lib/handlers/mailer.recipients.form.handler.php
         *
         * Output is expected in $params['recipients_groups'] as:
         * group_id => array(
         *    'name'    => string: group header
         *    'content' => string: HTML content
         *    'opened'  => bool: whether the group is opened by default; defaults to false.
         *    'included_in_all_contacts ' => boolean: true to hide this group when 'all contacts' are selected; defaults to false.
         * )
         * group_id should be prefixed with plugin id, or just be equal to it in simple cases.
         *
         * @param array[string]array $params['campaign'] row from mailer_message
         * @param array[string]array $params['recipients'] id => value from mailer_message_recipients
         * @param array[string]array $params['recipients_groups'] OUTPUT: array (group_id => array)
         * @return void
         */
        $params = array(
            'campaign' => $campaign,        // input
            'recipients' => $recipients,    // input
            'recipients_groups' => array(), // output
        );
        wa()->event('recipients.form', $params);
        // additional email must be the last one
        if (array_key_exists('flat_list', $params['recipients_groups'])) {
            $flat = $params['recipients_groups']['flat_list'];
            unset($params['recipients_groups']['flat_list']);
            $params['recipients_groups']['flat_list'] = $flat;
        }
        return $params['recipients_groups'];
    }

    protected function saveRecipientsFromPost($campaign, &$recipients)
    {
        if (!waRequest::post() ||
            ($campaign['status'] > 0 && $campaign['status'] != mailerMessageModel::STATUS_PENDING) ||
            mailerHelper::campaignAccess($campaign) < 2) {
            return false;
        }

        $mrm = new mailerMessageRecipientsModel();

        // Delete list by id if specified.
        if (waRequest::post('delete')) {
            $list_id = waRequest::post('list_id');
            if (isset($recipients[$list_id])) {
                $mrm->deleteById($list_id);
                unset($recipients[$list_id]);
                return true;
            }
            return false;
        }

        // recpients records to remove
        $remove_ids = waRequest::post('remove_ids');
        if (!$remove_ids || !is_array($remove_ids)) {
            $remove_ids = array();
        }
        $remove_ids = array_flip($remove_ids);

        // Delete list by id if specified.
        if (waRequest::post('all_contacts')) {
            $remove_ids += $mrm->getGroupedByMessage($campaign['id']);
        }

        // Recipients records to add
        $add_values = (array)waRequest::post('add_values');
        if (!$add_values) { // || !is_array($add_values)
            $add_values = array();
        }
        $add_values = array_flip($add_values);

        // Remove $remove_ids from $recipients, and remove values present in both $add_values and $recipients
        // (and therefore not need to be updated in DB), from $add_values and $remove_ids.
        foreach($recipients as $id => $value) {
            if (isset($add_values[$value])) {
                unset($add_values[$value]);
                unset($remove_ids[$id]);
            } else if (isset($remove_ids[$id])) {
                unset($recipients[$id]);
            }
        }

        // Save to DB and update $recipients
        $changed = false;
        if ($remove_ids) {
            $mrm->deleteById(array_keys($remove_ids));
            $changed = true;
        }
        if ($add_values) {
            $changed = true;
            foreach(array_keys($add_values) as $value) {
                $value = trim($value);
                if (strlen($value) <= 0) {
                    continue;
                }
                $list_id = $mrm->insert(array(
                    'message_id' => $campaign['id'],
                    'value' => $value,
                ));
                $recipients[$list_id] = $value;
            }
        }

        return $changed;
    }
}

