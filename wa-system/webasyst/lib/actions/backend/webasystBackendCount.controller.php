<?php
/**
  * Counts to show in header near applications' icons.
  * Saves its cache in session with key 'apps-count'.
  */
class webasystBackendCountController extends waJsonController
{
    public function execute()
    {
        // counts to show in page header near app names
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

        // announcements
        $user = wa()->getUser();
        $am = new waAnnouncementModel();
        $data = $am->getByApps($user->getId(), array_keys($apps), $user['create_datetime']);
        $announcements = array();
        foreach ($data as $row) {
            // show no more than 1 message per application
            if (isset($announcements[$row['app_id']]) && count($announcements[$row['app_id']]) >= 1) {
                continue;
            }
            $announcements[$row['app_id']][] = waDateTime::format('datetime', $row['datetime']).': '.$row['text'];
        }

        if ($announcements) {
            $announcements_html = '';
            foreach ($announcements as $app_id => $texts) {
                $announcements_html .= '<a href="#" rel="'.$app_id.'" class="wa-announcement-close">'._ws('[close]').'</a><p>';
                $announcements_html .= implode('<br />', $texts);
                $announcements_html .= '</p>';
            }
            if ($announcements_html) {
                $announcements_html = '<div id="wa-announcement">'.$announcements_html.'</div>';
            }
            $this->response['__announce'] = $announcements_html;
        }
    }
}
