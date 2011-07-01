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
    foreach ($apps as $app_id => $app) {
        if (isset($app['img'])) {
            $img = '<img src="'.$root_url.$app['img'].'" alt="">';
        } else {
            $img = '';
        }
        $apps_html .= '<li id="wa-app-'.$app_id.'"'.($app_id == $current_app ? ' class="selected"':'').'><a href="'.$backend_url.$app_id.'/">'.$img.' '.$app['name'].'</a></li>';
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
            if (!empty($url_info['scheme']) && $url_info['scheme'] != 'http') {
                $url_name = $url_info['scheme'].'://';
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
        $announcements[$row['app_id']][] = $row['text'];
    }
    $announcements_html = '';
    foreach ($announcements as $app_id => $texts) {
        $announcements_html .= '<a href="#" rel="'.$app_id.'" class="wa-announcement-close" title="close">x</a><p>';
        $announcements_html .= implode('<br />', $texts);
        $announcements_html .= '</p>';
    }
    if ($announcements_html) {
        $announcements_html = '<div id="wa-announcement">'.$announcements_html.'</div>';
    }

    $logout = _ws('logout');
    $userpic = '<img width="32" height="32" src="'.$user->getPhoto(32).'" alt="">';
    $username = htmlspecialchars($user['name']);

    // If the user has access to contacts app then show a link to his profile
    if (wa()->getUser()->getRights('contacts', 'backend')) {
        $userpic = '<a href="'.$backend_url.'contacts/#/contact/'.$user['id'].'">'.$userpic.'</a>';
        $username = '<a href="'.$backend_url.'contacts/#/contact/'.$user['id'].'" id="wa-my-username">'.$username.'</a>';
    }
    
    $more = _ws('more');

    $html = <<<HTML
<script type="text/javascript">var backend_url = "{$backend_url}";</script>
<script id="wa-header-js" type="text/javascript" src="{$root_url}wa-content/js/jquery-wa/wa.header.js"></script>
{$announcements_html}
<div id="wa-header" class="minimize1d">
    <div id="wa-account">
        <h3>{$app_settings_model->get('webasyst', 'name', 'Webasyst')}</h3>
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
    <div id="wa-applist">
        <ul>
            {$apps_html}
			<li>
				<a href="#" class="inline-link" id="wa-moreapps"><i class="icon10 darr" id="wa-moreapps-arrow"></i><b><i>{$more}</i></b></a>
			</li>            
        </ul>
    </div>
</div>
HTML;
    return $html;
}

function wa_url()
{
    return waSystem::getInstance()->getRootUrl();
}

function wa_backend_url()
{
    return waSystem::getInstance()->getConfig()->getBackendUrl(true);
}

/** print_r() all arguments inside <pre> and die(). */
function wa_print_r() {
    echo '<pre rel="waException">';
    foreach(func_get_args() as $v) {
        echo "\n".print_r($v, TRUE);
    }
    echo "</pre>\n";
    exit;
}

// !!! should probably move it to other file?
/** Wrapper around create_function() that caches functions it creates to avoid memory leaks */
function wa_lambda($args, $body) {
    static $fn = array();
    $hash = $args.md5($args.$body).md5($body);
    if(!isset($fn[$hash])) {
        $fn[$hash] = create_function($args, $body);
    }
    return $fn[$hash];
}

// EOF
