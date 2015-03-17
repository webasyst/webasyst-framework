<?php
/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-installer
 */
header('Cache-Control: no-cache, must-revalidate');
header('Content-Type: text/html; charset=UTF-8;');
header('Pragma: no-cache');
header('Connection: close');

@ini_set("magic_quotes_runtime", 0);
if (version_compare('5.4', PHP_VERSION, '>') && function_exists('set_magic_quotes_runtime') && get_magic_quotes_runtime()) {
    @set_magic_quotes_runtime(false);
}

$init_path = dirname(__FILE__).'/lib/init.php';
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
    if (isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
    }
}

if (!file_exists($init_path)) {
    throw new Exception("File <b>wa-installer/lib/init.php</b> not found");
}
require_once($init_path);

$t = new waInstallerLocale();
$lang = $t->getLocale();

$path = preg_replace('@(^|/)[^/]+$@', '', $_SERVER['REQUEST_URI']);

if (!empty($_SERVER['SCRIPT_NAME'])) {
    $path = $_SERVER['SCRIPT_NAME'];
} elseif (!empty($_SERVER['PHP_SELF'])) {
    $path = $_SERVER['PHP_SELF'];
} else {
    $path = '';
}
$path = preg_replace('!/[^/]*$!', '/', $path);

$host = preg_replace('@:80(/|/?$)@', '', $_SERVER['HTTP_HOST']).($path ? "/{$path}/" : '/');
$host = preg_replace('@(([^:]|^)/)/{1,}@', '$1', $host);

setcookie('auth_token', null, -1, $path);
/**
 * @param $stages
 * @param waInstallerLocale $t
 * @return string
 */
function parseLog($stages, &$t)
{
    $log = '';
    foreach ($stages as $info) {
        $info['datetime'] = date('D, H:i:s O', intval($info['stage_start_time']));
        $log .= <<<HTML
        <p><span>{$info['datetime']}</span> <span class="i-app-name">{$t->_($info['chunk_id'])}</span> {$t->_($info['stage_name'])}&nbsp;
HTML;
        if ($info['stage_status'] == 'heartbeat') {
            $stage_progress = isset($info['stage_progress']) ? sprintf('%d%%', $info['stage_progress']) : '';
            $log .= <<<HTML
            <i class="icon16 loading"></i><em class="in-progress">{$stage_progress}{$t->_($info['stage_status'])}</em>
HTML;
        } elseif ($info['stage_status'] == 'error') {
            $log .= <<<HTML
            <i class="icon10 no"></i> <em class="error">{$t->_($info['stage_status'])} {$info['error']}</em>
HTML;
        } else {
            $log .= <<<HTML
            <i class="icon10 yes"></i> <em class="success">{$t->_($info['stage_status'])}</em>
HTML;
        }
        $log .= <<<HTML
            </p>
HTML;
    }
    return $log;
}

if (isset($_GET['source']) && ($_GET['source'] == 'ajax')) {
    $installer = new waInstaller(waInstaller::LOG_DEBUG);
    $state = $installer->getState();
    if (!isset($state['stage_status']) || (($state['stage_name'] != waInstaller::STAGE_NONE) && ($state['heartbeat'] > (waInstaller::TIMEOUT_RESUME + 5))) || (($state['stage_name'] == waInstaller::STAGE_NONE) && ($state['heartbeat'] === false)) || (($state['stage_status'] == waInstaller::STATE_ERROR) && ($state['heartbeat'] > 4))) {

        print 'RESTART:'.'start!';
        exit;
    } elseif (false || (($state['stage_status'] == waInstaller::STATE_ERROR) && ($state['heartbeat'] > 1)) || (($state['stage_name'] == waInstaller::STAGE_UPDATE) && ($state['stage_status'] == waInstaller::STATE_COMPLETE) && ($state['heartbeat'] > 3))) {
        print 'COMPLETE:'.'error or complete';
        exit;
    }
    $log = parseLog($installer->getFullState('raw'), $t);
    $response = <<<HTML
<h1>{$t->_('Files')}&nbsp;<i class="icon16 loading"></i></h1>
<div class="i-log" id="ajax-log">{$log}</div>
<!--
<p>{$t->_('Extracting Webasyst archive...')}</p>
-->

HTML;
    print 'PROGRESS:'.$response;
    exit;
}

$steps = array();
$error = '';
$warning = '';
$checked = true;
$steps[] = array( //0 - Welcome
    'title' => '',
    'next'  => $t->_('Continue'),
);
$steps[] = array( //1 - System requirements
    'title' => $t->_('System requirements'),
    'next'  => $t->_('Continue'),
);
$steps[] = array( //2 - Extract files
    'title' => $t->_('Files'),
    'next'  => $t->_('Continue'),
);

$steps[] = array( //3 - Setup Database properties
    'title' => $t->_('Settings'),
    'next'  => $t->_('Connect'),
);

$next = $t->_('Retry');
$step = isset($_POST['step']) ? intval($_POST['step']) : (isset($_GET['step']) ? intval($_GET['step']) : 0);
$steps_count = count($steps);
$step = max(0, min($steps_count, $step));
$next_step = $step;

switch ($step) {
    case 0:
        $next_step = $step + 1;
        $next = $steps[$step]['next'];
        $extra = '';
        if (file_exists(dirname(__FILE__).'/../wa-sources/')) {
            $extra = <<<HTML
<input type="checkbox" value="1" name="check_latest" id="check_latest">
<label for="check_latest">{$t->_('Check available updates')}</label>
<br>
HTML;
            $extra = <<<HTML
<input type="hidden" value="0" name="check_latest">
HTML;
        } else {
            $extra = <<<HTML
<input type="hidden" value="1" name="check_latest">
HTML;
        }
        $locales = waInstallerLocale::listAvailable();
        $select_locale = '';
        if ($locales) {
            $select_locale .= '<br><select name="lang" id="wa-installer-locale-select">';
            foreach ($locales as $locale) {
                $t_item = new waInstallerLocale($locale);
                $selected = ($locale == $lang) ? ' selected="selected"' : '';
                $select_locale .= <<<HTML
<option value="{$locale}"{$selected}>{$t_item->_($locale)}</option>
HTML;
            }
            $select_locale .= '</select>';
        } else {
            $select_locale = <<<HTML
<input type="hidden" value="{$lang}" name="lang">
HTML;
        }

        $content = <<<HTML
<!-- welcome text -->
<div class="i-welcome">
	<h1 class="i-url"><span>http://</span>{$host}</h1>
		<p>{$t->_('Webasyst Installer will deploy archive with Webasyst system files and apps in this folder.')}<br >
		</p>

		{$extra}
		<input type="submit" value="{$t->_('Install Webasyst')}" class="button green large" id="wa-installer-submit">
		<br>
		<br>
		{$select_locale}
</div>
HTML;
        break;
    case 1:
        //download list of system requirements
        try {
            if (!file_exists($init_path)) {
                throw new Exception("File <b>wa-installer/lib/init.php</b> not found");
            }

            require_once($init_path);
            if (!class_exists('waInstallerApps')) {
                throw new Exception('Class <b>waInstallerApps</b> not found');
            }

            $requirements_path = dirname(__FILE__).'/lib/config/requirements.php';
            if (!file_exists($requirements_path)) {
                throw new Exception('Internal requirements file not found');
            }


            $requirements = null;
            waInstallerApps::setLocale($lang);
            $passed = waInstallerApps::checkRequirements($requirements, true);
            $check_latest = (isset($_POST['check_latest']) && $_POST['check_latest']) ? 1 : 0;
            $extra = <<<HTML
<input type="hidden" value="{$check_latest}" name="check_latest">
HTML;
            $requirements_output = '<div class="i-log"><ul class="menu-v with-icons">';
            if ($passed) {
                try {
                    if ($check_latest) {
                        //not supported since 2.0

                        $fw_items = array();

                        foreach ($fw_items as $item) {
                            $requirements += $item['requirements'];
                        }
                    } else {
                        //TODO attempt check local requirements
                    }
                } catch (Exception $ex) {
                    $passed = false;
                    $requirements_output .= <<<HTML
	<li>
		<i class="icon16 no"></i><strong class="large">{$ex->getMessage()}</strong>
	</li>
HTML;
                }
            }

            //TODO sort requirements
            $success = $passed;

            foreach ($requirements as $requirement) {
                if ($requirement['warning']) {
                    $requirement['warning'] = nl2br($requirement['warning']);
                }
                if (!$requirement['passed']) {

                    $success = false;
                    $requirements_output .= <<<HTML
	<li>
		<i class="icon16 no"></i><strong class="large">{$requirement['name']}</strong>
		<span class="hint i-error">{$requirement['warning']}<!-- placeholder --></span>
		<br>
		<span class="hint">{$requirement['description']}<!-- placeholder --></span>
	</li>
HTML;

                } else {
                    if (!$requirement['warning']) {

                        $requirements_output .= <<<HTML
	<li>
		<i class="icon16 yes"></i><strong class="large">{$requirement['name']} <span class="hint">{$requirement['note']}<!-- placeholder --></span></strong>
		<br>
		<span class="hint">{$requirement['description']}<!-- placeholder --></span>
	</li>
HTML;
                    } else {
                        $requirements_output .= <<<HTML
	<li>
		<i class="icon16 no-bw"></i><strong class="large">{$requirement['name']}</strong>
		<span class="hint">{$requirement['warning']}<!-- placeholder --></span>
		<br>
		<span class="hint">{$requirement['description']}<!-- placeholder --></span>
	</li>
HTML;
                    }
                }
            }
            $requirements_output .= '</ul></div>';
            $requirements_output .= "\n<!-- \n".var_export($requirements, true)."\n-->\n";
            if ($success) {
                $next_step = $step + 1;
                $next = $steps[$step]['next'];
                $content = <<<HTML
<h1>{$t->_('Web Server')}</h1>
                {$extra}
<p class="">{$t->_('Server fully satisfies Webasyst system requirements.')}</p>
                {$requirements_output}
HTML;
            } else {
                $content = <<<HTML
<h1>{$t->_('Web Server')}</h1>
                {$extra}
<p class="i-error">{$t->_('Server does not meet Webasyst system requirements. Installer can not proceed with the installation unless all requirements are satisfied.')}</p>
                {$requirements_output}
HTML;
            }
        } catch (Exception $e) {
            $content = <<<HTML
<h1>{$t->_('Web Server')}</h1>
            {$extra}
<p class="i-error">{$t->_('An error occurred while attempting to validate system requirements')}</p>
<p>{$e->getMessage()}</p>
HTML;
        }
        break;
    case 2:
        try {
            if (!file_exists($init_path)) {
                throw new Exception("File <b>wa-installer/lib/init.php</b> not found");
            }

            require_once($init_path);
            if (!class_exists('waInstallerApps')) {
                throw new Exception('Class <b>waInstallerApps</b> not found');
            }
            if (!class_exists('waInstaller')) {
                throw new Exception('Class <b>waInstaller</b> not found');
            }

            $urls = array();
            $local_path = dirname(__FILE__).'/../wa-sources/';
            $apps = array();
            $plugins = array();
            if (!isset($_POST['complete']) || !$_POST['complete']) {
                if (file_exists($local_path) && is_dir($local_path)) {
                    $cwd = getcwd();
                    chdir($local_path);
                    $pattern = '{*.tar.gz,wa-apps/*.tar.gz,wa-apps/*/plugins/*.tar.gz,wa-apps/*/themes/*.tar.gz}';
                    foreach (glob($pattern, GLOB_BRACE) as $path) {
                        if (preg_match('@^([\\w%0-9\\-!]+)\\.tar\\.gz$@', basename($path), $matches)) {
                            $decoded = dirname($path).'/';
                            if ($decoded == './') {
                                $decoded = '';
                            }

                            $decoded .= urldecode($matches[1]);
                            $urls[] = array(
                                'source' => $local_path.$path,
                                'target' => $decoded,
                                'slug'   => $decoded,
                            );
                            if (preg_match('@wa-apps/([\\w\\d\\-]+)$@', $decoded, $matches)) {
                                $apps[] = $matches[1];
                            } elseif (preg_match('@wa-apps/([\\w\\d\\-]+)/plugins/([\\w\\d\\-]+)$@', $decoded, $matches)) {
                                if (!isset($plugins[$matches[1]])) {
                                    $plugins[$matches[1]] = array();
                                }
                                $plugins[$matches[1]][] = $matches[2];
                            }
                        }
                    }
                    chdir($cwd);
                }

                if (!count($urls)) {
                    //not supported since 2.0
                    $list = array();
                    $urls = array();
                    foreach ($list as $target => $item) {
                        if (isset($item['target'])) {
                            $target = $item['target'];
                        }
                        $urls[] = array(
                            'source' => $item['download_url'],
                            'target' => $target,
                            'slug'   => $item['slug'],
                        );

                        if (preg_match('@wa-apps/([\\w\\d\\-]+)$@', $target, $matches)) {
                            $apps[] = $matches[1];
                        }
                    }
                }
            }

            $installer_apps = new waInstallerApps();
            waInstallerApps::setLocale($t->getLocale());
            $installer = new waInstaller(waInstaller::LOG_DEBUG);
            if ($urls && $installer->update($urls)) {
                foreach ($apps as $app) {
                    $installer_apps->installWebAsystApp($app);
                    if (!empty($plugins[$app])) {
                        foreach ($plugins[$app] as $plugin) {
                            $installer_apps->updateAppPluginsConfig($app, $plugin);
                        }
                    }
                }
            } else {
                $state = $installer->getState();
                if (isset($state['stage_status']) && ($state['stage_status'] == waInstaller::STATE_ERROR)) {
                    throw new Exception($state['error']);
                }
            }
            $log = parseLog($installer->getFullState('raw'), $t);
            $next_step = $step + 1;
            $next = $steps[$step]['next'];
            $content = <<<HTML
<h1>{$t->_('Files')}&nbsp;<i class="icon16 yes"></i></h1>
<div class="i-log">{$log}</div>
<p class="i-success">{$t->_('All files successfully extracted. Click "Continue" button below.')}&nbsp;<i class="icon16 yes"></i></p>
HTML;

        } catch (Exception $e) {
            $content = <<<HTML
<h1>{$t->_('Files')}&nbsp;<i class="icon16 no"></i></h1>
<p class="i-error">{$t->_('An error occurred during the installation')}</p>
<p>{$e->getMessage()}</p>
HTML;
        }
        break;
    case 3:
        $checked = false;
        $db_options = array(
            'host'     => 'localhost',
            'port'     => false,
            'user'     => 'root',
            'password' => '',
            'database' => '',
        );

        $htaccess_path = dirname(__FILE__).'/../.htaccess';
        if (!file_exists($htaccess_path) && ($fp = fopen($htaccess_path, 'w'))) {
            $htaccess_content =
                <<<HTACCESS

<FilesMatch "\.md5$">
    Deny from all
</FilesMatch>

DirectoryIndex index.php
Options -Indexes
# Comment the following line, if option Multiviews not allowed here
Options -MultiViews

AddDefaultCharset utf-8

<ifModule mod_rewrite.c>
    RewriteEngine On
    # Uncomment the following line, if you are having trouble
    #RewriteBase /

    RewriteCond %{REQUEST_URI} !\.(js|css|jpg|jpeg|gif|png)$
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L,QSA]
</ifModule>

<ifModule mod_headers.c>
    <FilesMatch "\.(jpg|jpeg|png|gif|js|css)$">
    Header set Cache-Control "max-age=3153600, public"
    </FilesMatch>
</ifModule>


HTACCESS;
            fwrite($fp, $htaccess_content);
            fclose($fp);
        }

        if (isset($_POST['db'])) {
            if (!is_array($_POST['db'])) {
                $_POST['db'] = array();
            }
            if (!is_array($_POST['config'])) {
                $_POST['config'] = array();
            }

            foreach ($db_options as $field => &$default) {
                if (isset($_POST['db'][$field])) {
                    $default = $_POST['db'][$field];
                }
                unset($default);
            }
            try {
                //check Connection for database;
                if (!extension_loaded('mysql')) {
                    throw new Exception($t->_('PHP extension mysql required'));
                }
                if ($link = @mysql_connect($db_options['host'], $db_options['user'], $db_options['password'])) {
                    if (@mysql_select_db($db_options['database'], $link)) {
                        //allow store settings
                        if (!file_exists($init_path)) {
                            throw new Exception("File <b>wa-installer/lib/init.php</b> not found");
                        }

                        require_once($init_path);
                        if (!class_exists('waInstallerApps')) {
                            throw new Exception('Class <b>waInstallerApps</b> not found');
                        }
                        if (mysql_query('SELECT 1 FROM `wa_app_settings` WHERE 0')) {
                            throw new Exception($t->_('Webasyst cannot be installed into "%s" database because this database already contains Webasyst tables. Please specify connection credentials for another MySQL database.', $db_options['database']));
                        } elseif ($result = mysql_query('SHOW TABLES')) {
                            if ($count = mysql_num_rows($result)) {
                                $warning = $t->_('The database already contains %d tables.', $count);
                            }
                        }

                        $installer_apps = new waInstallerApps();
                        if (extension_loaded('mysqli')) {
                            $db_options['type'] = 'mysqli';
                        } else {
                            $db_options['type'] = 'mysql';
                        }
                        if (strpos($db_options['host'], ':')) {
                            $db_options['port'] = '';
                            list($db_options['host'], $db_options['port']) = explode(':', $db_options['host'], 2);
                        }
                        $installer_apps->updateDbConfig($db_options);
                        mysql_close($link);

                        $installer_apps->setGenericOptions($_POST['config']);

                        $checked = true;
                    } else {
                        $error_text = mysql_error($link);
                        $error_no = mysql_errno($link);
                        throw new Exception($t->_('Failed to connect to the "%s" database. (%s)', $db_options['database'], "#{$error_no}: {$error_text}"));
                    }

                } else {
                    $error_text = htmlentities(mysql_error(), ENT_QUOTES, 'utf-8');
                    $error_no = mysql_errno();
                    throw new Exception($t->_('Failed to connect to "%s" MySQL database server. (%s)', $db_options['host'], "#{$error_no}: {$error_text}"));
                }
            } catch (Exception $e) {
                if (!empty($link) && is_resource($link)) {
                    mysql_close($link);
                }
                $error = "<p class=\"i-error\">".$e->getMessage()."</p>";
            }
        } else {
            $next = $steps[$step]['next'];
        }
        foreach ($db_options as &$option) {
            $option = htmlentities($option, ENT_QUOTES, 'utf-8');
            unset($option);
        }
        $mod_rewrite = waInstallerApps::getGenericConfig('mod_rewrite', true) ? '1' : '0';
        $content = <<<HTML
<h1>{$t->_('MySQL database')}</h1>
<p>{$t->_('Enter connection credentials for the MySQL database which will be used by Webasyst to store system and application data.')}</p>
{$error}

<div class="fields form">
	<div class="field-group">
		<div class="field">
			<div class="name">{$t->_('Web Server')}:</div>
			<div class="value">
				<input name="db[host]" type="text" class="large" value="{$db_options['host']}" >
			</div>
		</div>
		<div class="field">
			<div class="name">{$t->_('User')}:</div>
			<div class="value">
				<input name="db[user]" type="text" class="large" value="{$db_options['user']}">
			</div>
		</div>
		<div class="field">
			<div class="name">{$t->_('Password')}:</div>
			<div class="value">
				<input name="db[password]" type="password" class="large" value="{$db_options['password']}">
			</div>
		</div>
		<div class="field">
			<div class="name">{$t->_('Database Name')}:</div>
			<div class="value">
				<input name="db[database]" type="text" class="large" value="{$db_options['database']}" >
			</div>
		</div>
	</div>
	<input type="hidden" name="config[mod_rewrite]" value="{$mod_rewrite}" id="input_mod_rewrite">
</div>

<p class="clear-left i-hint">{$t->_('If you do not know what should be entered here, please contact your hosting provider technical support.')}</p>


HTML;


        if ($checked) {
            $next_step = ++$step;
        } else {
            break;
        }

    case 4:
        $is_https = false;
        if (isset($_SERVER['HTTPS'])) {
            $is_https = (strtolower($_SERVER['HTTPS']) != 'off') ? true : false;
        } elseif (isset($_SERVER['SCRIPT_URI']) && preg_match('/^https:\/\//i', $_SERVER['SCRIPT_URI'])) {
            $is_https = true;
        }
        try {

            $config_path = dirname(__FILE__).'/../wa-config/SystemConfig.class.php';
            $config_dir = dirname(__FILE__).'/../wa-config/';
            if (!file_exists($config_dir)) {
                mkdir($config_dir);
                $htaccess_path = $config_dir.'.htaccess';
                if (!file_exists($htaccess_path)) {
                    if ($fp = @fopen($htaccess_path, 'w')) {
                        fwrite($fp, "Deny from all\n");
                        fclose($fp);
                    } else {
                        throw new Exception("Error while trying to protect a directory wa-config with htaccess");
                    }
                }
            }

            if (!file_exists($config_path)) {
                if ($fp = fopen($config_path, 'w')) {
                    $config_content = <<<PHP
<?php

require_once dirname(__FILE__).'/../wa-system/autoload/waAutoload.class.php';
waAutoload::register();

class SystemConfig extends waSystemConfig
{

}

PHP;
                    fwrite($fp, $config_content);
                    fclose($fp);
                } else {
                    throw new Exception("Error while create SystemConfig at wa-config");
                }
            }

            $url = $is_https ? 'https' : 'http';
            $login_path = waInstallerApps::getGenericConfig('mod_rewrite', true) ? 'webasyst/' : 'index.php/webasyst/';
            $content = <<<HTML

<div class="i-welcome">
	<h1>{$t->_('Installed!')}</h1>
	<p>{$t->_('Webasyst is installed and ready.')}</p>

	<p><a id="redirect_url" href="//{$host}{$login_path}?lang={$lang}" class="large"><strong>{$url}://{$host}<span class="highlighted underline">{$login_path}</span></strong></a> <i class="icon10 yes"></i></p>
	<p class="clear-left i-hint">{$t->_('Remember this address. This is the address for logging into your Webasyst backend.')}</p>

	<br><br><br>
	<p id="redirect_message" style="display:none;">{$t->_('Finalizing installation...')} <i class="icon16 loading"></i></p>
	<p>{$warning}</p>

</div>
HTML;
            $next_step = $step + 1;
        } catch (Exception $e) {
            $content = <<<HTML
<h1>{$t->_('Files')}&nbsp;<i class="icon16 no"></i></h1>
<p class="i-error">{$t->_('An error occurred during the installation')}</p>
<p>{$e->getMessage()}</p>
HTML;
        }
        break;
    default:
        $content = 'Hello world';
        break;
}
$progress = '';
$count = count($steps);

if ($step > 0 && $step < $count) {
    $progress .= <<<HTML

<div class="dialog-buttons">
<div class="dialog-buttons-gradient">
<div class="i-progress-indicator">
HTML;
    for ($i = 1; $i < $steps_count; $i++) {
        $class = ($i < $step) ? 'passed' : (($i == $step) ? 'current' : 'next');
        $progress .= <<<HTML
        <span id="i-progress-step-{$i}" class="{$class}" title="{$steps[$i]['title']}">{$i}</span>
HTML;
    }
    $color = (($next_step > $step && !$error) || (in_array($step, array(3)) && !$error)) ? 'green' : 'grey';
    $progress .= <<<HTML
		</div><!--
		-->
		<input type="submit" value="{$next}" class="button {$color}" id="wa-installer-submit" >
		<a href="{$t->_('install_quide_url')}" target="_blank" class="wa-help-link"><span>{$t->_('Installation Guide')}</span> <i class="icon10 new-window"></i></a>
		</div>
</div>
HTML;
}

$css_path = dirname(__FILE__).'/css/wa-installer.css';
$css_min_path = dirname(__FILE__).'/css/wa-installer.clean.css';
$inline_css = '';
if (false && file_exists($css_min_path)) {
    $inline_css = file_get_contents($css_min_path);
} elseif (file_exists($css_path)) {
    $inline_css = file_get_contents($css_path);
}
if ($inline_css) {
    $inline_css = <<<HTML
<style type="text/css">
/* inline css from wa-installer/css/wa-installer.css*/
    {$inline_css}
</style>
HTML;

}
$js_path = dirname(__FILE__).'/js/wa-installer.js';
$inline_js = '';
if (file_exists($js_path)) {
    $inline_js = file_get_contents($js_path);
    $inline_js = <<<JS
<script type="text/javascript">
/* inline js from wa-installer/js/wa-installer.js*/
{$inline_js}
wai.options.lang='{$lang}';
</script>
JS;

}
$index = <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd"><html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{$t->_('Webasyst Installer')}</title>
{$inline_css}
{$inline_js}
</head>
<body>

	<div id="wa-installer">

		<div class="dialog" id="wa-install-dialog">
			<div class="dialog-background"></div>
			<div class="dialog-window">
			<form action="install.php" method="POST" id="install_form">
				<div class="dialog-content" id="dialog-content">
				<input type="hidden" name="step" value="{$next_step}">
				<input type="hidden" name="lang" value="{$lang}">
				<input type="hidden" name="complete" value="0" id="install_form_complete">
					<div class="dialog-content-indent" id="content-wrapper">


					{$content}
					</div>
				</div>
				{$progress}

			</form>
			</div>


		</div> <!-- .dialog -->

	</div> <!-- #wa-login -->
</body>
</html>
HTML;

print $index;
