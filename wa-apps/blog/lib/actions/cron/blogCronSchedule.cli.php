<?php

/**
 * @deprecated
 * @see blogScheduleCli
 */

class blogCronScheduleCli extends waCliController
{

    public function execute()
    {
        ob_start();
        $app = $this->getApp();

        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set($app, 'cron_schedule', time());

        waFiles::create($this->getConfig()->getPath('log').'/'.$app.'/');
        $log_file = "{$app}/cron.txt";

        $post_model = new blogPostModel();
        $params = array('datetime'=>date("Y-m-d H:i:s"),'status'=>blogPostModel::STATUS_SCHEDULED);
        $posts_schedule = $post_model->select("id,blog_id,contact_id,status,datetime")->where('datetime <= s:datetime AND status=s:status',$params)->fetchAll();
        if ($posts_schedule) {
            foreach ($posts_schedule as $post) {
                try {
                    waLog::log("Attempt publishing post with id [{$post['id']}]",$log_file);
                    $data = array(
                    	"status" => blogPostModel::STATUS_PUBLISHED,
                    );
                    waLog::log($post_model->updateItem($post['id'], $data,$post)?"success":"fail", $log_file);
                } catch(Exception $ex) {
                    waLog::log($ex->getMessage(),$log_file);
                    waLog::log($ex->getTraceAsString(),$log_file);
                }
            }
        }

        $action = __FUNCTION__;
        /**
         * @event cron_action
         * @param string $action
         * @return void
         */
        wa()->event('cron_action',$action);
        if ($log = ob_get_clean()) {
            waLog::log($log,$log_file);
        }
    }
}
//EOF