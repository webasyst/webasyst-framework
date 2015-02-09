<?php

/**
 * Campaign editor, settings customization.
 *
 * Shows campaign settings form, and accepts submit from it.
 * After submit, if everything is OK, prepares the campaign for sending and marks it as being sent.
 *
 * See mailerCampaignsSendController and mailerMessage->send() for what happens next.
 */
class mailerCampaignsSettingsAction extends waViewAction
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
        if ($campaign['status'] != mailerMessageModel::STATUS_DRAFT && $campaign['status'] != mailerMessageModel::STATUS_PENDING) {
            echo "<script>window.location.hash = '#/campaigns/report/{$campaign_id}/';</script>";
            exit;
        }

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 2) {
            throw new waException('Access denied.', 403);
        }

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Prepare google-analytics defaults
//        if (empty($params['google_analytics'])) {
//            // If previous campaign had google analytics turned on, then turn it on by default
//            $sql = "SELECT MAX(m.id)
//                    FROM mailer_message AS m
//                        JOIN mailer_message_params AS mp
//                            ON mp.message_id=m.id
//                    WHERE m.status > 0
//                        AND mp.name='google_analytics'";
//            $ga_message_id = $mm->query($sql)->fetchField();
//
//            if ($ga_message_id) {
//                $sql = "SELECT MAX(id) FROM mailer_message WHERE status > 0";
//                if ($ga_message_id == $mm->query($sql)->fetchField()) {
//                    $params['google_analytics'] = 1;
//                }
//            }
//        }
        if (empty($params['google_analytics_utm_source'])) {
            $params['google_analytics_utm_source'] = 'newsletter';
        }
        if (empty($params['google_analytics_utm_medium'])) {
            $params['google_analytics_utm_medium'] = 'email';
        }
        if (empty($params['google_analytics_utm_campaign'])) {
            $params['google_analytics_utm_campaign'] = strtolower(waLocale::transliterate($campaign['subject']));
            $params['google_analytics_utm_campaign'] = preg_replace('~[^a-z0-9]+~u', '_', preg_replace('~[`\'"]~', '', $params['google_analytics_utm_campaign']));
            $params['google_analytics_utm_campaign'] = trim($params['google_analytics_utm_campaign'], '_');
//            if (strlen($params['google_analytics_utm_campaign']) <= 5) {
//                $params['google_analytics_utm_campaign'] = '';
//            }
        }

        // List of possible senders
        $sm = new mailerSenderModel();
        $senders = $sm->getAll();

        // Create the default sender if no senders exist
        if (!$senders) {
            $asm = new waAppSettingsModel();
            $email = $asm->get('webasyst', 'email');
            if ($email) {
                $id = $sm->insert(array(
                    'name' => $asm->get('webasyst', 'name'),
                    'email' => $email,
                ));
                $spm = new mailerSenderParamsModel();
                $spm->save($id, array(
                    'type' => 'default',
                ));
                $senders = $sm->getAll();
            }
        }

        // List of return-paths
        $rpm = new mailerReturnPathModel();
        $return_paths = $rpm->select('id, email')->fetchAll();

        // Create the default return-path, if possible to deduce it from system mail config
        if (!$return_paths) {
            $mail_config = wa()->getConfig()->getConfigFile('mail');
            if (!empty($mail_config) && !empty($mail_config['default'])) {
                $mail_config = $mail_config['default'];
                $rp = array(
                    'ssl' => 0,
                );
                foreach(array(
                    'login' => 'login',
                    'password' => 'password',
                    'server' => 'pop3_host',
                    'port' => 'pop3_port',
                    'ssl' => 'pop3_ssl',
                    'email' => 'login',
                ) as $rp_key => $conf_key) {
                    if (isset($mail_config[$conf_key])) {
                        $rp[$rp_key] = $mail_config[$conf_key];
                    }
                }
                if (count($rp) >= 6) {
                    $rp_id = $rpm->insert($rp);
                    $return_paths[] = array(
                        'id' => $rp_id,
                        'email' => $rp['email'],
                    );
                }
            }
        }

        mailerHelper::assignCampaignSidebarVars($this->view, $campaign, $params);
//        $params['action'] = 'NameAndCountRecipients'; // __not__ update table with draft recipients because we just open page, only count nonunique recipients
        $this->view->assign('return_paths', $return_paths);
        $this->view->assign('campaign', $campaign);
        $this->view->assign('senders', $senders);
        $this->view->assign('params', $params);
        $this->view->assign('sending_speed_values', wa('mailer')->getConfig()->getAvailableSpeeds());
//        $this->view->assign('unique_recipients', mailerHelper::countRecipients($campaign, $params));
    }
}

