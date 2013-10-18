<?php

/**
 * Cron job to publish scheduled posts
 */
class blogEmailsubscriptionCli extends waCliController
{
    public function run()
    {
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set(array('blog', 'emailsubscription'), 'last_emailsubscription_cron_time', time());
        
        $model = new blogEmailsubscriptionLogModel();
        $row = $model->getByField('status', 0);
        if ($row) {
            $post_id = $row['post_id'];
            $post_model = new blogPostModel();
            $post = $post_model->getById($post_id);

            $blog_model = new blogBlogModel();
            $blog = $blog_model->getById($post['blog_id']);

            $subject = $blog['name'].': '.$post['title'];
            $post_title = htmlspecialchars($post['title']);
            if ($blog['status'] == blogBlogModel::STATUS_PUBLIC) {
                $post_url = blogPost::getUrl($post);
            } else {
                $app_settings_model = new waAppSettingsModel();
                $post_url = $app_settings_model->get(array('blog', 'emailsubscription'), 'backend_url', wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl());
                $post_url .= "/blog/?module=post&id=".$post_id;
            }
            $blog_name = htmlspecialchars($blog['name']);
            $body = '<html><body>'.sprintf(_wp("New post in the blog “%s”"), $blog_name).': <strong><a href="'.$post_url.'">'.$post_title.'</a></strong></body></html>';
            $message = new waMailMessage();
            $message->setEncoder(Swift_Encoding::getBase64Encoding());
            $message->setSubject($subject);
            $message->setBody($body);
            $rows = $model->getByField(array('status' => 0, 'post_id' => $post_id), true);

            $message_count = 0;
            
            foreach ($rows as $row) {
                try {
                    $message->setTo($row['email'], $row['name']);
                    $status = $message->send() ? 1 : -1;
                    $model->setStatus($row['id'], $status);
                    if ($status) {
                        $message_count++;
                    }
                } catch (Exception $e) {
                    $model->setStatus($row['id'], -1, $e->getMessage());
                }
            }
            
            /**
             * Notify plugins about sending emailsubscripition
             * @event followup_send
             * @return void
             */
            wa()->event('emailsubscription_send', $message_count);
            
        }
    }
}

