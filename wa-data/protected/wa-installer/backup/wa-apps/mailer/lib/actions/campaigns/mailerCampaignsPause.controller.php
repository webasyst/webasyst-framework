<?php

/**
 * Pause given campaign that is currently sending or resume paused campaign.
 */
class mailerCampaignsPauseController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::request('id', 0, 'int');
        if (!$id) {
            return;
        }

        $mm = new mailerMessageModel();
        $campaign = $mm->getById($id);
        if (!$campaign) {
            return;
        }

        if (mailerHelper::campaignAccess($campaign) < 2) {
            return;
        }

        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($id);

        if (waRequest::request('pause')) {
            if ($campaign['status'] != mailerMessageModel::STATUS_SENDING) {
                return;
            }

            $mm->updateById($id, array('status' => mailerMessageModel::STATUS_SENDING_PAUSED));

            // Calculate total sending time and save in message params
            $send_datetime = ifempty($params['fake_send_timestamp'], strtotime($campaign['send_datetime']));
            $mpm->save($id, array('total_sending_time' => time() - $send_datetime + 5), array('fake_send_timestamp'));

            /**@/**
             * @event campaign.pause
             *
             * Campaign just switched to PAUSED state
             *
             * @param array[string]array $params['campaign'] row from mailer_message
             * @param array[string]array $params['params'] campaign params from mailer_message_params, key => value
             * @return void
             */
            $evt_params = array(
                'campaign' => $campaign,
                'params' => $params,
            );
            wa()->event('campaign.pause', $evt_params);

        } else if (waRequest::request('resume')) {
            if ($campaign['status'] != mailerMessageModel::STATUS_SENDING_PAUSED) {
                return;
            }

            // Calculate fake sending start datetime for estimated time
            $total_sending_time = ifempty($params['total_sending_time'], 0);
            $mpm->save($id, array('fake_send_timestamp' => time() - $total_sending_time), array('total_sending_time'));
            $mm->updateById($id, array('status' => mailerMessageModel::STATUS_SENDING));

            /**@/**
             * @event campaign.resume
             *
             * Campaign just switched to SENDING state after being paused
             *
             * @param array[string]array $params['campaign'] row from mailer_message
             * @param array[string]array $params['params'] campaign params from mailer_message_params, key => value
             * @return void
             */
            $evt_params = array(
                'campaign' => $campaign,
                'params' => $params,
            );
            wa()->event('campaign.resume', $evt_params);
        }
    }
}

