<?php
/**
  * Counts to show in header near applications' icons.
  * Saves its cache in session with key 'apps-count'.
  */
class webasystBackendCountController extends waJsonController
{
    public function execute()
    {
        $apps = $this->getUser()->getApps(false);
        $root_path = wa()->getConfig()->getRootPath();
        foreach ($apps as $app_id => $app) {
            $class_name = $app_id.'Config';
            if (file_exists($root_path.'/wa-apps/'.$app_id.'/lib/config/'.$class_name.'.class.php')) {
                $n = wa($app_id)->getConfig()->onCount();
                if ($n !== null) {
                    $this->response[$app_id] = $n;
                }
            }
        }

        // cache counts in session
        wa()->getStorage()->write('apps-count', array_filter($this->response));
    }
}
