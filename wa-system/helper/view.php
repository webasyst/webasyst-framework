<?php

function wa_header()
{
    $system = waSystem::getInstance();
    if ($system->getEnv() == 'frontend') {
        return '';
    }
    $root_url = $system->getRootUrl();
    $backend_url = $system->getConfig()->getBackendUrl(true);
    $user = $system->getUser();
    $apps = $user->getApps();
    $current_app = $system->getApp();

    $app_settings_model = new waAppSettingsModel();

    $apps_html = '';
    $applist_class = '';
    $counts = wa()->getStorage()->read('apps-count');
    if (is_array($counts)) {
        $applist_class .= ' counts-cached';
    }
    foreach ($apps as $app_id => $app) {
        if (isset($app['img'])) {
            $img = '<img '.(!empty($app['icon'][96]) ? 'data-src2="'.$root_url.$app['icon'][96].'"' : '').' src="'.$root_url.$app['img'].'" alt="">';
        } else {
            $img = '';
        }

        $count = '';
        $app_url = $backend_url.$app_id.'/';
        if ($counts && isset($counts[$app_id])) {
            if (is_array($counts[$app_id])) {
                $app_url = $counts[$app_id]['url'];
                $n = $counts[$app_id]['count'];
            } else {
                $n = $counts[$app_id];
            }
            if ($n) {
                $count = '<span class="indicator">'.$n.'</span>';
            }
        }
        $apps_html .= '<li id="wa-app-'.$app_id.'"'.($app_id == $current_app ? ' class="selected"':'').'><a href="'.$app_url.'">'.$img.' '.$app['name'].$count.'</a></li>';
    }

    if ($system->getRequest()->isMobile(false)) {
        $top_url = '<a href="'.$backend_url.'?mobile=1">mobile version</a>';
    } else {
        $url = $app_settings_model->get('webasyst', 'url', $system->getRootUrl(true));
        $url_info = @parse_url($url);
        if ($url_info) {
            $url_name = '';
            if (empty($url_info['scheme'])) {
                $url = 'http://'.$url;
            }
            if (isset($url_info['host'])) {
                $url_name .= $url_info['host'];
            }

            if (isset($url_info['path'])) {
                if ($url_info['path'] == '/' && !isset($url_info['query'])) {

                } else {
                    $url_name .= $url_info['path'];
                }
            }
            if (isset($url_info['query'])) {
                $url_name .= '?'.$url_info['query'];
            }
        } else {
            $url = $url_name = $system->getRootUrl(true);
        }
        $top_url = '<a target="_blank" href="'.$url.'">'.$url_name.'</a>';
    }
    $announcement_model = new waAnnouncementModel();
    $data = $announcement_model->getByApps($user->getId(), array_keys($apps), $user['create_datetime']);
    $announcements = array();
    foreach ($data as $row) {
        // show no more than 1 message per application
        if (isset($announcements[$row['app_id']]) && count($announcements[$row['app_id']]) >= 1) {
            continue;
        }
        $announcements[$row['app_id']][] = $row['text'].' <span class="hint">'.waDateTime::format('humandatetime', $row['datetime']).'</span>';
    }
    $announcements_html = '';
    foreach ($announcements as $app_id => $texts) {
        $announcements_html .= '<a href="#" rel="'.$app_id.'" class="wa-announcement-close inline-link" title="close"><b><i>'._ws('Close').'</i></b></a><p>';
        $announcements_html .= implode('<br />', $texts);
        $announcements_html .= '</p>';
    }
    if ($announcements_html) {
        $announcements_html = '<div id="wa-announcement">'.$announcements_html.'</div>';
    }

    $logout = _ws('logout');
    $userpic = '<img width="32" height="32" src="'.$user->getPhoto(32).'" alt="">';
    $username = htmlspecialchars(waContactNameField::formatName($user), ENT_QUOTES, 'utf-8');

    // If the user has access to contacts app then show a link to his profile
    if (wa()->appExists('contacts')) {
        require_once(wa()->getConfig()->getAppsPath('contacts', 'lib/models/contactsRights.model.php'));
        try {
            $cr = new contactsRightsModel();
        } catch (waDbException $e) {
            wa('contacts');
            $cr = new contactsRightsModel();
        }
        if ($user->getRights('contacts', 'backend') && $cr->getRight(null, $user['id'])) {
            $userpic = '<a href="'.$backend_url.'contacts/#/contact/'.$user['id'].'">'.$userpic.'</a>';
            $username = '<a href="'.$backend_url.'contacts/#/contact/'.$user['id'].'" id="wa-my-username">'.$username.'</a>';
        } else {
            $userpic = '<a href="'.$backend_url.'?module=profile">'.$userpic.'</a>';
            $username = '<a href="'.$backend_url.'?module=profile" id="wa-my-username">'.$username.'</a>';
        }
    }

    $more = _ws('more');

    if ($applist_class) {
        $applist_class = ' class="'.trim($applist_class).'"';
    }

    $company_name = htmlspecialchars($app_settings_model->get('webasyst', 'name', 'Webasyst'), ENT_QUOTES, 'utf-8');

    $version = wa()->getVersion();
    $html = <<<HTML
<script type="text/javascript">var backend_url = "{$backend_url}";</script>
{$announcements_html}
<div id="wa-header">
    <div id="wa-account">
        <h3>{$company_name}</h3>
        {$top_url}
    </div>
    <div id="wa-usercorner">
        <div class="profile image32px">
            <div class="image">
                {$userpic}
            </div>
            <div class="details">
                {$username}
                <p class="status"></p>
                <a class="hint" href="{$backend_url}?action=logout">{$logout}</a>
            </div>
        </div>
    </div>
    <div id="wa-applist" {$applist_class}>
        <ul>
            {$apps_html}
            <li>
                <a href="#" id="wa-moreapps"></a>
            </li>
        </ul>
    </div>
</div>
<script id="wa-header-js" type="text/javascript" src="{$root_url}wa-content/js/jquery-wa/wa.header.js?v{$version}"></script>
HTML;
    return $html;
}

function wa_url($absolute = false)
{
    return waSystem::getInstance()->getRootUrl($absolute);
}

function wa_backend_url()
{
    return waSystem::getInstance()->getConfig()->getBackendUrl(true);
}
