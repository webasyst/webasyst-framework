<?php

/**
 * Cron job to send reminders to authors about overdue or close to deadline posts
 */
class blogReminderCli extends waCliController
{
    public function run($params = NULL)
    {
        $app_settings_model = new waAppSettingsModel();
        $contact_settings_model = new waContactSettingsModel();
        
        $app_settings_model->set('blog', 'last_reminder_cron_time', time());
        
        // remider settings for all users
        $reminders = $contact_settings_model->select('contact_id, value')->
                where("app_id='blog' AND name='reminder'")->
                fetchAll('contact_id', true);
        if (!$reminders) {
            return;
        }
        
        $time = time();
        
        // do job no more one time in 24 hours
        $last_cron_times = $contact_settings_model->select('contact_id')->
                where("app_id='blog' AND name='last_reminder_cron_time' AND value <= " . ($time - 86400))->
                fetchAll('contact_id', true);

        $reminders_allowed = array_keys($last_cron_times);
        if (!$reminders_allowed) {
            return;
        }

        $post_model = new blogPostModel();
        
        $backend_url = $app_settings_model->get('blog', 'backend_url', wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl());
        
        $message_count = 0;
        
        foreach ($reminders_allowed as $contact_id) {
            $days = $reminders[$contact_id];
            // get all deadline posts for this contact
            $posts = $post_model->select("id, title, datetime")->where(
                "status='".blogPostModel::STATUS_DEADLINE."' AND contact_id=".$contact_id.
                " AND datetime < '".date('Y-m-d H:i:s', $time + $days * 86400)."'"
            )->order('datetime')->fetchAll();
            if ($posts) {
                $contact = new waContact($contact_id);
                $email = $contact->get('email', 'default');
                
                $message = new waMailMessage(_w('Scheduled blog posts'), $this->getMessage($posts, $time, $backend_url));
                
                try {
                    $message->setTo($email);
                    if ($message->send()) {
                        $message_count++;
                    }
                } catch (Exception $e) {
                }
                
            }
            $contact_settings_model->set($contact_id, 'blog', 'last_reminder_cron_time', $time);
        }
        
        /**
         * Notify plugins about sending reminder
         * @event reminder_send
         * @return void
         */
        wa()->event('reminder_send', $message_count);
        
    }
    
    public function getMessage($posts, $time, $backend_url)
    {
        $message = sprintf(
            _w("You have blog posts scheduled for publication on the %s blog:"),
            wa()->accountName()
        )."<br>";
        if ($posts) {
            $message .= "<ul>";
            foreach ($posts as $post) {
                $days_left = (int) ((strtotime($post['datetime']) - $time) / 86400);
                $link = "<li><a href='".$backend_url."/blog/?module=post&id={$post['id']}&action=edit'".( $days_left<0 ? ' style="color: red; font-weight: bold;"':'' ).">".$post['title']."</a> &mdash; ".wa_date('humandate', $post['datetime'])." (".(
                        $days_left == 0 ?
                                '<b>'._w('today').'</b>'
                            :
                                _w('in %d day', 'in %d days', $days_left)
                ).")"."</li>";
                $message .= $days_left < 0 ? "<span style='color: red;'>".$link."</span>" : $link;
            }
            $message .= "</ul>";
        }
        return $message;
    }
    
}

