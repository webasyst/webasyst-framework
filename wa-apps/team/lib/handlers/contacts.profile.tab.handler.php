<?php
class teamContactsProfileTabHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $contact_id = $params;
        $contact = new waContact($contact_id);

        $is_superadmin = wa()->getUser()->isAdmin();
        $is_own_profile = wa()->getUser()->getId() == $contact_id;
        $has_access_team = wa()->getUser()->getRights('team', 'backend');

        $backend_url = wa()->getConfig()->getBackendUrl(true);

        $is_old_contacts = waRequest::request('module', '', 'string') == 'contacts'
            && waRequest::request('action', '', 'string') == 'info'
            && wa()->appExists('contacts');
        if ($is_old_contacts) {
            $is_old_contacts = version_compare(wa()->getVersion('contacts'), '1.2.0') < 0;
        }

        $old_app = wa()->getApp();
        wa('team', 1);

        $result = array();

        // Timeline (activity)
        if ($has_access_team) {
            $result[] = array(
                'id' => 'activity',
                'title' => _w('Timeline'),
                'url' => $backend_url.'team/?module=profile&action=activity&id='.$contact_id,
                'count' => '',
            );
        }

        if (!$is_old_contacts) {

            // Contact info
            if ($has_access_team || $is_own_profile) {
                $result[] = array(
                    'id' => 'info',
                    'count' => '',
                    'title' => _w('Contact info'),
                    'html' => new waLazyDisplay(new teamProfileInfoAction(array(
                        'id' => $contact_id,
                    ))),
                );
            }

            // User access
            if ($is_superadmin) {
                $result[] = array(
                    'id' => 'access',
                    'title' => _w('User access'),
                    'url' => $backend_url.'team/?module=profile&action=access&id='.$contact_id,
                    'count' => '',
                );
            } elseif ($is_own_profile) {
                $result[] = array(
                    'id' => 'access',
                    'count' => '',
                    'title' => _w('User access'),
                    'html' => new waLazyDisplay(new teamProfileAccessAction(array(
                        'id' => $contact_id,
                    ))),
                );
            }
        }

        // Stats
        if ($has_access_team) {
            $result[] = array(
                'id' => 'stats',
                'title' => _w('Stats'),
                'url' => $backend_url.'team/?module=profile&action=stats&id='.$contact_id,
                'count' => '',
            );
        }

        $more_tabs = wa()->event(array('team', 'profile_tab'), $params);
        foreach(ifempty($more_tabs, array()) as $plugin_id => $one_or_more_links) {
            if (isset($one_or_more_links['html']) || isset($one_or_more_links['url']) || isset($one_or_more_links['id'])) {
                $links = array($one_or_more_links);
            } else {
                $links = array_values($one_or_more_links);
            }
            $result = array_merge($result, $links);
        }

        wa($old_app, 1);
        return ifempty($result, null);
    }
}
