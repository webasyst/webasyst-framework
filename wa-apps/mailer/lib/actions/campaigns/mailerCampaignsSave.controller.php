<?php

/**
 * Controller that saves the campaign (message). Common for all editor steps.
 */
class mailerCampaignsSaveController extends waJsonController
{
    public function execute()
    {
        $message_id = waRequest::post('id', 0, 'int');

        // Check whether campaign can be modified
        $mm = new mailerMessageModel();
        if ($message_id) {
            $campaign = $mm->getById($message_id);
            if (!$campaign || $campaign['status'] > 0 && $campaign['status'] != mailerMessageModel::STATUS_PENDING ) {
                $this->response = $message_id;
                return;
            }

            // Access control
            if (mailerHelper::campaignAccess($campaign) < 2) {
                throw new waException('Access denied.', 403);
            }
        } else {
            // Access control
            if (mailerHelper::isAuthor() < 2) {
                throw new waException('Access denied.', 403);
            }
        }

        $data = waRequest::post('data', array());
        $sender_params = array();
        // Populate from_name and from_email from sender data
        if (!empty($data['sender_id'])) {
            $sm = new mailerSenderModel();
            $sender = $sm->getById($data['sender_id']);
            if ($sender) {
                // Update message data using data from sender parameters.
                $spm = new mailerSenderParamsModel();
                $sender_params = $spm->getBySender($data['sender_id']);
                $data['from_name'] = trim($sender['name']);
                $data['from_email'] = trim($sender['email']);
                $data['reply_to'] = trim(ifempty($sender_params['reply_to'], ''));
            } else {
                $data['sender_id'] = 0;
            }
        }

        // Clean up data when illegal sender given, or no sender for new message
        if (empty($data['sender_id']) && (!$message_id || array_key_exists('sender_id', $data))) {
            $asm = new waAppSettingsModel();
            $data['sender_id'] = 0;
            $data['from_name'] = $asm->get('webasyst', 'name');
            $data['from_email'] = $asm->get('webasyst', 'email');
        }

        // Save message
        if ($message_id) {
            $mm->updateById($message_id, $data);
        } else {
            $data['create_datetime'] = date("Y-m-d H:i:s");
            $data['create_contact_id'] = $this->getUser()->getId();
            foreach(array('subject','body','name','from_name','from_email','reply_to','return_path') as $fld) {
                if (empty($data[$fld])) {
                    $data[$fld] = '';
                }
            }
            $message_id = $mm->insert($data);

            $this->logAction('composed_new_campaign');
        }

        if (!empty($data['body'])) {
            mailerHelper::copyMessageFiles($message_id, $data['body']);
        }

        // Save message params
        $new_params = waRequest::post('params');
        unset($sender_params['dkim_pub_key'], $sender_params['dkim_pvt_key']);
        $new_params['sender_params'] = serialize($sender_params);
        $delete_old_params = waRequest::post('delete_old_params');
        if ($new_params === null || !is_array($new_params)) {
            $new_params = array();
        }
        if ($delete_old_params === null || !is_array($delete_old_params)) {
            $delete_old_params = array();
        }

        $mpm = new mailerMessageParamsModel();
        $mpm->save($message_id, $new_params, $delete_old_params);

        $this->response = $message_id;
    }
}

