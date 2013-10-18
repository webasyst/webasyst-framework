<?php

/**
 * Cron job to publish scheduled posts
 */
class blogScheduleCli extends waCliController
{
    public function run()
    {
        $app = $this->getApp();
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set($app, 'last_schedule_cron_time', time());
        
        waFiles::create($this->getConfig()->getPath('log').'/'.$app.'/');
        $log_file = "{$app}/schedule.txt";
        
        $post_model = new blogPostModel();
        $params = array(
            'datetime' => date("Y-m-d H:i:s"),
            'status' => blogPostModel::STATUS_SCHEDULED
        );
        $posts_schedule = $post_model->select("id, blog_id, contact_id, status, datetime")->
                where('datetime <= s:datetime AND status=s:status', $params)->
                fetchAll();
        if ($posts_schedule) {
            foreach ($posts_schedule as $post) {
                try {
                    waLog::log("Attempt publishing post with id [{$post['id']}]", $log_file);
                    $data = array(
                    	"status" => blogPostModel::STATUS_PUBLISHED,
                    );
                    $r = $post_model->updateItem($post['id'], $data, $post);
                    waLog::log($r ? "success": "fail", $log_file);
                } catch(Exception $ex) {
                    waLog::log($ex->getMessage(), $log_file);
                    waLog::log($ex->getTraceAsString(), $log_file);
                }
            }
        }
    }
}

