<?php
/**
 * Counts to show in header near applications' icons.
 * Saves its cache in session with key 'apps-count'.
 */
class webasystBackendCountController extends waJsonController
{
    public function execute()
    {
        // close session
        wa()->getStorage()->close();

        $apps = wa()->getApps(true);

        $user = wa()->getUser();
        $is_admin = $user->isAdmin();
        if (!$is_admin) {
            $right_model = new waContactRightsModel();
            $rights = $right_model->getApps(-$user->getId(), 'backend', true, false);
            foreach ($apps as $app_id => $app_info) {
                if (!isset($rights[$app_id])) {
                    unset($apps[$app_id]);
                }
            }
        }

        $app_settings_model = new waAppSettingsModel();
        foreach ($apps as $app_id => $app_info) {
            $app_path = wa()->getAppPath(null, $app_id);
            $class_name = $app_id.'Config';
            if ($app_settings_model->get($app_id, 'update_time') && file_exists($app_path.'/lib/config/'.$class_name.'.class.php')) {
                try {
                    $n = wa($app_id)->getConfig()->onCount();
                    $this->parseItemCount($app_id, $n);
                } catch(Exception $ex) {
                    waLog::log('Error '.$ex->getCode().': '.$ex->getMessage());
                }
            }
        }

        // cache counts in session
        wa()->getStorage()->write('apps-count', array_filter($this->response));
    }

    protected function parseItemCount($app_id, $count)
    {
        if ($count === null) {
            return;
        }

        if (!is_array($count)) {
            return $this->response[$app_id] = $count;
        }

        if (is_array($count) && count($count) === 2 && array_key_exists('count', $count) && array_key_exists('url', $count)) {
            return $this->response[$app_id] = $count;
        } elseif (is_array($count)) {
            foreach ($count as $item_id => $n) {
                $item_id = ($app_id === $item_id) ? $item_id : $app_id.'.'.$item_id;
                $this->parseItemCount($item_id, $n);
            }
        }
    }
}
