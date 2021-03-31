<?php

class waDesignActions extends waActions
{

    protected $design_url = '#/';
    protected $themes_url = '#/themes/';

    protected $options = array(
        'container'  => true,
        'save_panel' => true,
        'js'         => array(
            'ace'    => true,
            'editor' => true,
        ),
        'is_ajax'    => false
    );

    /**
     * The action includes a preview of the design theme for the admin on all settlements of the site.
     * At the moment, this mechanism is used only for trial design themes.
     */
    public function setPreviewAction()
    {
        $key = waRequest::getThemeStorageKey();
        $theme_id = waRequest::post('theme_id', null, waRequest::TYPE_STRING_TRIM);
        wa()->getStorage()->set($key, $theme_id);
        $this->displayJson(true);
    }

    public function defaultAction()
    {
        $app_id = $this->getAppId();
        $app = wa()->getAppInfo($app_id);

        if (empty($app['themes'])) {
            throw new waException('App does not support themes.');
        }

        $themes = wa()->getThemes($app_id, true);
        $routes = $this->getRoutes();

        $themes_routes = $this->getThemesRoutes($themes, $routes);

        $t_id = waRequest::get('theme');
        $route = array();
        if ($t_id) {
            foreach ($themes_routes as $r) {
                if (is_array($r) && $r['theme'] == $t_id) {
                    $route = $r;
                    break;
                }
            }
        } elseif ($themes_routes && is_array($themes_routes[0])) {
            $route = $themes_routes[0];
            $t_id = ifset($themes_routes[0]['theme'], 'default');
        } else {
            $t_id = 'default';
            if (empty($themes[$t_id])) {
                reset($themes);
                $t_id = key($themes);
            }
        }
        $theme = $themes[$t_id];

        $routing_url = false;
        if (wa()->appExists('site')) {
            wa('site');
            $routing_url = wa()->getAppUrl('site').'#/routing/';
        }
        $current_url = $this->design_url.'theme='.$theme['id'];
        if ($route) {
            $current_url .= '&domain='.urlencode($route['_domain']).'&route='.$route['_id'];
        }

        $this->setTemplate('Design.html', true);

        $this->display(array(
            'current_url'   => $current_url,
            'design_url'    => $this->design_url,
            'themes_url'    => $this->themes_url,
            'theme'         => $theme,
            'route'         => $route,
            'themes'        => $themes,
            'themes_routes' => $themes_routes,
            'app_id'        => $app_id,
            'app'           => $app,
            'routing_url'   => $routing_url,
            'options'       => $this->options,
        ));
    }

    public function editAction()
    {
        $app_id = $this->getAppId();
        $app = wa()->getAppInfo($app_id);
        $theme_id = waRequest::get('theme');
        $theme = new waTheme($theme_id, $app_id);
        if ($theme['type'] == waTheme::TRIAL) {
            throw new waException('Access denied', 403);
        }
        $theme_files = $theme['files'];

        if ($theme->parent_theme_id) {
            foreach ($theme_files as $file => &$f) {
                if (!empty($f['parent'])) {
                    $parent_file = $theme->parent_theme->getFile($file);
                    if (!empty($parent_file['modified'])) {
                        $f['modified'] = $parent_file['modified'];
                    }
                }
            }
            unset($f);
        }
        if (($f = waRequest::get('file')) !== '') {
            if (!$f) {
                $files = $theme['files'];
                if (isset($files['index.html'])) {
                    $f = 'index.html';
                } else {
                    ksort($files);
                    $f = key($files);
                }
            }
            $file = $theme->getFile($f);
            if (!$file) {
                $f = preg_replace('@(\\{1,}|/{2,})@', '/', $f);
                if (!$f
                    ||
                    preg_match('@(^|/)\.\./@', $f)
                    ||
                    !file_exists($theme->getPath().'/'.$f)
                ) {
                    $f = 'index.html';
                    $file = $theme->getFile($f);
                }
            }
            $file['id'] = $f;
            if ($theme->parent_theme_id && !empty($file['parent'])) {
                if (!waTheme::exists($theme->parent_theme_id, $app_id)) {
                    $theme_id = $theme->parent_theme_id;
                    if (strpos($theme_id, ':') !== false) {
                        list($app_id, $theme_id) = explode(':', $theme_id, 2);
                        $app = wa()->getAppInfo($app_id);
                    }
                    throw new waException(sprintf(_ws('Theme %s for “%s” app not found.'), $theme_id, $app['name']));
                }
                $path = $theme->parent_theme->getPath();
                $parent_file = $theme->parent_theme->getFile($f);
                if (empty($file['description'])) {
                    $file['description'] = ifset($parent_file['description'], '');
                }
                if (!empty($parent_file['modified'])) {
                    $file['modified'] = $parent_file['modified'];
                }
                if ($theme->parent_theme->type == waTheme::OVERRIDDEN) {
                    $file['has_original'] = $theme->parent_theme['type'] == file_exists(wa()->getAppPath('themes/'.$theme->parent_theme->id, $theme->parent_theme->app_id).'/'.$f);
                }
            } else {
                $path = $theme->getPath();
                if ($theme->type == waTheme::OVERRIDDEN) {
                    $file['has_original'] = $theme['type'] == file_exists(wa()->getAppPath('themes/'.$theme_id, $app_id).'/'.$f);
                }
            }
            $path .= '/'.$f;
            $content = file_exists($path) ? file_get_contents($path) : '';
            $file['content'] = $content;
        } else {
            $file = array(
                'id'          => null,
                'description' => '',
                'custom'      => true,
                'content'     => ''
            );
        }

        $routes = $this->getRoutes(true);
        $theme_usages = array();
        $theme_usages_decoded = null;
        $idna = new waIdna();

        foreach ($routes as $r) {
            if (empty($r['theme'])) {
                $r['theme'] = 'default';
            }
            if ($r['theme'] == $theme_id && $r['_domain'] != waRequest::get('domain') && $r['_id'] != waRequest::get('route')) {
                $theme_usages[] = htmlspecialchars($r['_domain'].'/'.$r['url']);
                $theme_usages_decoded[] = $idna->decode(htmlspecialchars($r['_domain'].'/'.$r['url']));
            }
        }

        $route_url = false;
        $route_url_decoded = null;
        if ($_d = waRequest::get('domain')) {
            $domain_routes = wa()->getRouting()->getByApp(wa()->getApp(), $_d);
            if (isset($domain_routes[waRequest::get('route')])) {
                $route_url = htmlspecialchars($_d.'/'.$domain_routes[waRequest::get('route')]['url']);
                $route_url_decoded = $idna->decode($route_url);
            }
        }

        $this->setTemplate('DesignEdit.html', true);

        $data = array(
            'options'              => $this->options,
            'app_id'               => $app_id,
            'design_url'           => $this->design_url,
            'app'                  => $app,
            'file'                 => $file,
            'theme_id'             => $theme_id,
            'theme'                => $theme,
            'theme_usages'         => $theme_usages,
            'theme_usages_decoded' => $theme_usages_decoded,
            'route_url'            => $route_url,
            'route_url_decoded'    => $route_url_decoded,
            'theme_files'          => $theme_files
        );

        if ($theme->parent_theme_id) {
            $data['parent_theme'] = $theme->parent_theme;
        }

        $this->display($data);
    }

    protected function getThemesRoutes(&$themes, $routes)
    {
        $hash = $this->getThemeHash();
        $preview_url = $routing_url = '';
        $domains = $themes_routes = array();

        if (wa()->appExists('site')) {
            wa('site');
            $model = new siteDomainModel();
            $domains = $model->select('id,name')->fetchAll('name', true);
            $routing_url = wa()->getAppUrl('site');
        }

        $domain = wa()->getRouting()->getDomain();
        foreach ($routes as $route) {
            $theme_id = (string)ifempty($route, 'theme', 'default');
            if (!isset($themes[$theme_id])) {
                $theme_id = 'default';
            }
            $route['theme'] = $theme_id;

            $themes[$theme_id]['is_used'] = true;
            if (isset($route['theme_mobile']) && isset($themes[$route['theme_mobile']])) {
                $themes[$route['theme_mobile']]['is_used'] = true;
            }
            $url = $route['_url'];
            if (!$preview_url && $route['_domain'] == $domain) {
                $preview_url = $url;
            }
            $route['_preview_url'] = $url;

            if (isset($domains[$route['_domain']]) && $this->getUser()->getRights('site', 'domain.'.$domains[$route['_domain']])) {
                $route['_routing_url'] = $routing_url.'?module=routing&action=edit&domain_id='.$domains[$route['_domain']].'&route='.$route['_id'];
            }
            $themes_routes[] = $route;
        }
        $preview_params = strpos($preview_url, '?') === false ? '?' : '&';
        $preview_params .= 'theme_hash='.$hash.'&set_force_theme=';
        foreach ($themes as $t_id => &$theme) {
            if (!isset($theme['preview_url'])) {
                $theme['preview_url'] = $preview_url;
                if ($preview_url && $theme['type'] !== waTheme::TRIAL) {
                    $theme['preview_url'] .= $preview_params.$t_id;
                }
            }
            $theme['preview_name'] = preg_replace('/^.*?\/\/(.*?)\?.*$/', '$1', $theme['preview_url']);
            if (!$theme['is_used']) {
                $themes_routes[] = $t_id;
            }
        }
        unset($theme);
        return $themes_routes;
    }

    /**
     *
     * @return string
     * @throws waDbException
     * @throws waException
     */
    protected function getThemeHash()
    {
        $asm = new waAppSettingsModel();
        $hash = $asm->get('webasyst', 'theme_hash');
        if ($hash && strpos($hash, '.') !== false) {
            $hash_parts = explode('.', $hash);
            if (time() - $hash_parts[1] > 14400) { // 24 hours
                $hash = null;
            }
        }
        if (!$hash) {
            $hash = md5(uniqid().mt_rand().time().mt_rand()).'.'.time();
            $asm->set('webasyst', 'theme_hash', $hash);
        }

        return md5($hash);
    }

    protected function getRoutes($all = false)
    {
        $routes = wa()->getRouting()->getByApp($this->getAppId());

        $result = array();
        $idna = new waIdna();
        foreach ($routes as $d => $domain_routes) {
            foreach (array_reverse($domain_routes, true) as $route_id => $route) {
                $route['_id'] = $route_id;
                $route['_domain'] = $d;
                $route['_url'] = waRouting::getUrlByRoute($route, $d);
                $route['_url_title'] = $d.'/'.waRouting::clearUrl($route['url']);
                $route['_domain_decoded'] = $idna->decode($d);
                $result[] = $route;
            }
        }
        return $result;
    }

    public function saveAction()
    {

        $app_id = $this->getAppId();
        $theme_id = waRequest::get('theme_id');
        $file = waRequest::get('file');

        $errors = null;

        $path = wa()->getDataPath('themes', true, $app_id, false);
        $this->checkAccess($path);

        // copy original theme
        $theme = new waTheme($theme_id, $app_id);
        try {
            if ($theme['type'] == waTheme::ORIGINAL) {
                $theme->copy();
            }

            // create file
            if (!$file) {
                // parent
                if (waRequest::post('type')) {
                    $file = waRequest::post('parent');
                    $theme->addFile($file, '', array('parent' => 1));
                } else {
                    $file = waRequest::post('file');
                    if ($this->checkFile($file, $errors)) {
                        $theme->addFile($file, waRequest::post('description'));
                    }
                }
                if (!$errors) {
                    if (!$theme->save()) {
                        $errors = _ws('Insufficient file access permissions to save theme settings');
                    } else {
                        $this->logAction('template_add', $file);
                    }
                }
            } else {
                if (waRequest::post('file') && ($file != waRequest::post('file'))) {
                    if (!$this->checkFile(waRequest::post('file'), $errors)) {
                        $this->displayJson(array(), $errors);
                        return;
                    }
                    $theme->removeFile($file);
                    $file = waRequest::post('file');
                    if (!$theme->addFile($file, waRequest::post('description'))->save()) {
                        $errors = _ws('Insufficient file access permissions to save theme settings');
                    } else {
                        $this->logAction('template_edit', $file);
                    }
                } else {
                    $f = $theme->getFile($file);
                    if (empty($f)) {
                        if ($this->checkFile($file, $errors)) {
                            $errors = _ws('Insufficient file access permissions to save theme settings');
                        }
                    }
                    if (!empty($theme['parent_theme_id']) && !empty($f['parent'])) {
                        $theme = new waTheme($theme['parent_theme_id']);
                        if ($theme['type'] == waTheme::ORIGINAL) {
                            $theme->copy();
                        }
                    }
                    if (!$theme->changeFile($file, waRequest::post('description'))) {
                        $errors = _ws('Insufficient file access permissions to save theme settings');
                    } else {
                        $this->logAction('template_edit', $file);
                    }
                }
                @touch($theme->getPath().'/'.waTheme::PATH);
            }
        } catch (waException $ex) {
            $errors = $ex->getMessage();
        }

        $response = array();
        if ($file && !$errors) {
            // update mtime of theme.xml
            @touch($path);
            $response['id'] = $file;
            switch ($ext = waFiles::extension($file)) {
                case 'css':
                case 'js':
                    $response['type'] = $ext;
                    break;
                default:
                    $response['type'] = '';
            }
            $response['theme'] = $theme_id;
            // if not parent
            if (!waRequest::post('type')) {
                $content = waRequest::post('content');
                $file_path = $theme->getPath().'/'.$file;
                if (!file_exists($file_path) || is_writable($file_path)) {
                    if ($content || file_exists($file_path)) {
                        $r = @file_put_contents($file_path, $content);
                        if ($r !== false) {
                            $r = true;
                            if (in_array($ext, array('css', 'js'))) {
                                $theme['edition'] = true;
                                $theme->save();
                            }
                        }
                    } else {
                        $r = @touch($file_path);
                    }
                } else {
                    $r = false;
                }
                if (!$r) {
                    $errors = _ws('Insufficient access permissions to save the file').' '.$file_path;
                }
            } else {
                $response['inherit'] = 1;
            }
        }

        $this->displayJson($response, $errors);
    }

    protected function checkAccess($path)
    {
        // create .htaccess to deny access to *.php and *.html files
        if (!file_exists($path.'/.htaccess')) {
            waFiles::create($path.'/');
            $htaccess = <<<HTACCESS
<FilesMatch "\.(php\d?|html)">
    Deny from all
</FilesMatch>

HTACCESS;
            @file_put_contents($path.'/.htaccess', $htaccess);
        }
    }

    protected function checkFile($file, &$errors = array())
    {
        if (!$file) {
            $errors = array(
                _ws('Please enter a filename'),
                'input[name=file]'
            );
            return false;
        }
        if (!preg_match("/^[a-z0-9_\.-]+$/i", $file)) {
            $errors = array(
                _ws('Only Latin letters (a—z, A—Z), numbers (0—9) and underline character (_) are allowed.'),
                'input[name=file]'
            );
            return false;
        }
        if (!preg_match("/\.(xml|xsl|html|js|css)$/i", $file)) {
            $errors = array(
                _ws('File should have one of the allowed extensions:').' .html, .css, .js, .xml, .xsl',
                'input[name=file]'
            );
            return false;
        }
        return true;
    }

    public function deleteAction()
    {
        $theme = waRequest::post('theme_id', 'default');
        $file = waRequest::post('file');

        $theme = new waTheme($theme, $this->getAppId());
        $theme->removeFile($file);
        $theme->save();
        $this->logAction('template_delete', $file);

        $this->displayJson(array());
    }

    public function themeUpdateAction()
    {
        $theme_id = waRequest::get('theme');
        $theme = new waTheme($theme_id);
        if ($theme['type'] == waTheme::TRIAL) {
            throw new waException('Access denied', 403);
        }

        if (waRequest::method() == 'post') {
            if (!waRequest::post("parent_only")) {
                if (waRequest::post('reset')) {
                    foreach (waRequest::post('reset') as $f) {
                        $theme->revertFile($f);
                    }
                }
                $theme->update(false);
            }

            if ($theme->parent_theme && ($theme->parent_theme->type == waTheme::OVERRIDDEN)) {
                if (waRequest::post('parent_reset')) {
                    foreach (waRequest::post('parent_reset') as $f) {
                        $theme->parent_theme->revertFile($f);
                    }
                }
                $theme->parent_theme->update(false);
            }
            $this->displayJson(array());
        } else {
            $theme_original = new waTheme($theme_id, true, 'original');
            $data = array(
                'theme'                  => $theme,
                'theme_original_version' => $theme_original->version,
                'theme_problem_files'    => $theme->problemFiles(),
            );
            if ($theme->parent_theme && ($theme->version == $theme_original->version) && ($theme->parent_theme->type == waTheme::OVERRIDDEN)) {
                $parent_theme_original = new waTheme($theme->parent_theme->id, $theme->parent_theme->app, 'original');
                $data['theme_original_version'] = $parent_theme_original->version;
                $data['parent_only'] = true;
                $data['parent_theme_problem_files'] = $theme->parent_theme->problemFiles();
            }


            if (!empty($data['theme_problem_files']) || !empty($data['parent_theme_problem_files'])) {
                $this->setTemplate('ThemeUpdateProblemDialog.html', true);
            } else {
                $this->setTemplate('ThemeUpdateDialog.html', true);
            }

            $this->display($data);
        }
    }

    public function themeUseAction()
    {
        $theme_id = waRequest::post('theme');
        $theme = new waTheme($theme_id);
        if ($theme['type'] == waTheme::TRIAL) {
            throw new waException('Access denied', 403);
        }
        $route = waRequest::post('route');

        $path = $this->getConfig()->getPath('config', 'routing');

        if (file_exists($path)) {
            $routes = include($path);
            if (!is_writable($path)) {
                $this->displayJson(array(), sprintf(_w('Settings could not be saved due to the insufficient file write permissions for the file "%s".'), 'wa-config/routing.php'));
                return;
            }
        } else {
            $routes = array();
        }

        if ($route == 'new') {
            $domain = waRequest::post('domain');
            $url = waRequest::post('url');
            if (!$url) {
                $url = '*';
            }
            $route_id = 0;
            foreach ($routes[$domain] as $r_id => $r) {
                if (is_numeric($r_id) && $r_id > $route_id) {
                    $route_id = $r_id;
                }
            }
            $route_id++;
            $app_id = $this->getAppId();
            $route = array(
                'url'          => $url,
                'app'          => $app_id,
                'theme'        => $theme_id,
                'theme_mobile' => $theme_id,
                'locale'       => wa()->getLocale(),
            );

            $app = wa($app_id)->getConfig()->getInfo();
            if (isset($app['routing_params']) && is_array($app['routing_params'])) {
                wa($app_id);
                foreach ($app['routing_params'] as $routing_param => $routing_param_value) {
                    if (is_callable($routing_param_value)) {
                        $app['routing_params'][$routing_param] = call_user_func($routing_param_value);
                    }
                }

                $route += $app['routing_params'];
            }

            if ($route['url'] == '*') {
                $routes[$domain][$route_id] = $route;
            } else {
                if (strpos($route['url'], '*') === false) {
                    if (substr($route['url'], -1) == '/') {
                        $route['url'] .= '*';
                    } elseif (substr($route['url'], -1) != '*' && strpos(substr($route['url'], -5), '.') === false) {
                        $route['url'] .= '/*';
                    }
                }
                $routes[$domain] = array($route_id => $route) + $routes[$domain];
            }
        } else {
            list($domain, $route_id) = explode('|', $route);
            if (!waRequest::post('mobile_only')) {
                $routes[$domain][$route_id]['theme'] = $theme_id;
            }
            $routes[$domain][$route_id]['theme_mobile'] = $theme_id;
        }

        waUtils::varExportToFile($routes, $path);
        $this->displayJson(array(
            'domain' => $domain,
            'route'  => $route_id,
            'theme'  => $theme_id
        ));
    }

    public function themeAction()
    {
        $app_id = $this->getAppId();
        $theme_id = waRequest::get('theme');
        $parent_themes = array();
        $apps = wa()->getApps();
        /**
         * @var waTheme $current_theme
         */
        $current_theme = null;

        foreach ($apps as $theme_app_id => $app) {
            if (!empty($app['themes']) && ($themes = wa()->getThemes($theme_app_id, true))) {
                $themes_data = array();
                foreach ($themes as $id => $theme) {
                    if (($app_id == $theme_app_id) && ($theme_id == $id)) {
                        $current_theme = $theme;
                    }
                    $themes_data[$id] = $theme->name;
                }
                if ($themes_data) {
                    $parent_themes[$theme_app_id] = array(
                        'name'   => $app['name'],
                        'img'    => $app['img'],
                        'themes' => $themes_data,
                    );
                }
            }
        }
        if (!$current_theme) {
            if (isset($parent_themes[$app_id]) && count($parent_themes[$app_id]['themes']) && ($default = key($parent_themes[$app_id]['themes']))) {
                $this->displayJson(array('redirect' => "{$this->design_url}theme={$default}&action=theme"));
            } else {
                $this->displayJson(array('redirect' => $this->themes_url));
            }
        } else {
            $current_locale = null;
            $routes = $this->getRoutes();
            $theme_routes = array();
            $preview_url = false;
            foreach ($routes as $r) {
                if ((waRequest::get('route') == $r['_id']) && !empty($r['locale'])) {
                    $current_locale = $r['locale'];
                }
                if (!$preview_url && $r['app'] == $app_id) {
                    $preview_url = $r['_url'];
                    if ($current_theme->type !== waTheme::TRIAL) {
                        $preview_url .= '?theme_hash='.$this->getThemeHash().'&set_force_theme='.$theme_id;
                    }
                }
            }
            $settings = $current_theme->getSettings(false, $current_locale);

            try {
                // Make sure parent theme is accessible
                $current_theme->parent_theme;
            } catch (waException $e) {
                $current_theme->parent_theme = 'default';
                $current_theme->save();
            }

            if ($current_theme->parent_theme) {
                $parent_settings = $current_theme->parent_theme->getSettings(false, $current_locale);
                foreach ($parent_settings as &$s) {
                    $s['parent'] = 1;
                }
                unset($s);
                foreach ($settings as $k => $v) {
                    $parent_settings[$k] = $v;
                }
                $settings = $parent_settings;
            }

            $settings = $this->convertSettingsToTree($settings);
            unset($settings['level']);

            $global_group_divideres = array();
            if (!empty($settings['items'])) {
                foreach ($settings['items'] as $index => $setting) {
                    if ($setting['control_type'] == 'group_divider' && $setting['level'] == 1 && !empty($setting['items'])) {
                        $global_group_divideres[$index] = $setting['name'];
                    }
                }
            }

            foreach ($this->getRoutes(true) as $r) {
                if (empty($r['theme'])) {
                    $r['theme'] = 'default';
                }
                if (empty($r['theme_mobile'])) {
                    $r['theme_mobile'] = 'default';
                }
                if (($r['theme'] == $theme_id) || ($r['theme_mobile'] == $theme_id)) {
                    $theme_routes[] = $r;
                }
            }

            $cover = false;
            if (file_exists($current_theme->getPath().'/cover.png')) {
                $cover = $current_theme->getUrl().'cover.png';
            } elseif ($current_theme->parent_theme && file_exists($current_theme->parent_theme->getPath().'/cover.png')) {
                $cover = $current_theme->parent_theme->getUrl().'cover.png';
            }

            $route_url = false;
            if ($_d = waRequest::get('domain')) {
                $domain_routes = wa()->getRouting()->getByApp(wa()->getApp(), $_d);
                if (isset($domain_routes[waRequest::get('route')])) {
                    $route_url = htmlspecialchars($_d.'/'.$domain_routes[waRequest::get('route')]['url']);
                }
            }

            $theme_original_version = false;
            $original_warning_requirements = array();
            if ($current_theme['type'] == waTheme::OVERRIDDEN) {
                $theme_original = new waTheme($current_theme->id, $current_theme->app_id, waTheme::ORIGINAL);
                $theme_original_version = $theme_original->version;
                $original_warning_requirements = $theme_original->getWarningRequirements();
            }

            $theme_parent_original_version = false;
            if ($current_theme->parent_theme && $current_theme->parent_theme->type == waTheme::OVERRIDDEN) {
                $theme_parent_original = new waTheme($current_theme->parent_theme->id, $current_theme->parent_theme->app_id, waTheme::ORIGINAL);
                $theme_parent_original_version = $theme_parent_original->version;
            }

            $support = $current_theme['support'];
            // Parse support from parent theme
            if (empty($support) && $current_theme->parent_theme && !empty($current_theme->parent_theme['support'])) {
                $support = $current_theme->parent_theme['support'];
            }

            // Prepare support website or email
            if (!empty($support)) {
                $email_validator = new waEmailValidator();
                if ($email_validator->isValid($support) && !preg_match('~^https?://~', $support)) {
                    $support = 'mailto:'.$support;
                } else {
                    if (!preg_match('~^https?://~', $support)) {
                        $support = 'http://'.$support;
                    }
                }
            }

            // Parse instruction from parent theme
            $instruction = $current_theme['instruction'];
            if (empty($instruction) && $current_theme->parent_theme && !empty($current_theme->parent_theme['instruction'])) {
                $instruction = $current_theme->parent_theme['instruction'];
            }

            // Prepare instruction site
            if (!empty($instruction) && !preg_match('~^https?://~', $instruction)) {
                $instruction = 'http://'.$instruction;
            }

            $theme_warning_requirements = $current_theme->getWarningRequirements();

            $theme_parent_warning_requirements = false;
            if ($current_theme->parent_theme) {
                $theme_parent_warning_requirements = $current_theme->parent_theme->getWarningRequirements();
            }

            $this->setTemplate('Theme.html', true);

            $this->display(array(
                'current_locale'                      => $current_locale,
                'routes'                              => $routes,
                'domains'                             => wa()->getRouting()->getDomains(),
                'preview_url'                         => $preview_url,
                'global_group_divideres'              => $global_group_divideres,
                'settings'                            => $settings,
                'design_url'                          => $this->design_url,
                'app'                                 => wa()->getAppInfo($app_id),
                'theme'                               => $current_theme,
                'support'                             => $support,
                'instruction'                         => $instruction,
                'theme_warning_requirements'          => $theme_warning_requirements,
                'theme_original_warning_requirements' => $original_warning_requirements,
                'theme_original_version'              => $theme_original_version,
                'theme_parent_original_version'       => $theme_parent_original_version,
                'theme_parent_warning_requirements'   => $theme_parent_warning_requirements,
                'options'                             => $this->options,
                'parent_themes'                       => $parent_themes,
                'theme_routes'                        => $theme_routes,
                'path'                                => waTheme::getThemesPath($app_id),
                'cover'                               => $cover,
                'route_url'                           => $route_url,
                'apps'                                => wa()->getApps(),
                'need_show_review_widget'             => $this->needShowReviewWidget($theme_id),
            ));
        }
    }

    /**
     * Convert flat list of theme settings into hierarchical tree structure
     * based on group divider levels.
     *
     * Each group_divider is allowed to have several "normal" settings at the begining
     * of its child 'items' array, and then may have several child group_dividers.
     * It is never allowed to alternate between group_dividers and "normal" settings.
     *
     * Ignores 'level' of everything except group_dividers. All items other than
     * group_dividers are treated as children of previous group_divider.
     *
     * @param array $settings_items
     * @return array
     * @throws waException
     */
    protected function convertSettingsToTree($settings_items)
    {
        if (empty($settings_items)) {
            return array();
        }

        // Insert initial fake divider if settings do not start with divider already
        $first_item = reset($settings_items);
        if ($first_item['control_type'] != 'group_divider') {
            array_unshift($settings_items, array(
                'var'          => waTheme::GENERAL_SETTINGS_DIVIDER,
                'control_type' => 'group_divider',
                'value'        => '',
                'group'        => '',
                'level'        => 1,
                'name'         => _ws('General settings'),
            ));
        }

        // First pass: put each setting into 'items' array of previous divider.
        // This does not create hierarchical structure yet, just moves non-dividers into groups
        // created by dividers
        $dividers_list = array();
        $last_divider = null;
        foreach($settings_items as $setting_var => $item) {
            $item['var'] = $setting_var;
            $item['level'] = ($item['level'] < 1) ? 1 : $item['level'];
            $item['level'] = ($item['level'] > 6) ? 6 : $item['level'];
            if ($item['control_type'] != 'group_divider') {
                if ($last_divider === null) {
                    throw new waException('this can not happen');
                }
                $last_divider['items'][$setting_var] = $item;
            } else {
                if (!isset($item['level']) || $item['level'] <= 0) {
                    throw waException::dump('Level is not set for', $item);
                }
                unset($last_divider);
                $item['items'] = array();
                $last_divider = $item;
                $dividers_list[] =& $last_divider;
            }
        }

        // Second pass: create hierarchical structure out of group_dividers in $dividers_list
        list($root, $_) = $this->oneTreeItemFrom($dividers_list, 0);
        return $root;
    }

    /**
     * Build a single tree section of given level from the start of $dividers_list (consuming items).
     * Return [ new_tree_section, rest_of_$dividers_list ]
     * @param array $dividers_list
     * @param int $needed_level
     * @return array
     */
    protected function oneTreeItemFrom($dividers_list, $needed_level)
    {
        // Can not build shit from empty list
        if (!$dividers_list) {
            return array(null, array());
        }
        // Can not build item of higher level if first item has lower level
        $first_item = reset($dividers_list);
        if ($first_item['level'] <= $needed_level - 1) {
            return array(null, $dividers_list);
        }

        if ($first_item['level'] == $needed_level) {
            // Start either from the first item in list if its level
            // is what we need...
            $item = array_shift($dividers_list);
        } else {
            // ...otherwise create an intermediate fake item of needed level
            $item = array(
                'level' => $needed_level,
                'items' => array(),
            );
        }

        // Extract items from the begining of $dividers_list
        // until they fit into our $item (i.e. belong under the $needed_level)
        // As soon as we meet something <= $needed_level, this loop stops.
        do {
            list($child_item, $dividers_list) = $this->oneTreeItemFrom($dividers_list, $item['level'] + 1);
            if ($child_item) {
                $item['items'][] = $child_item;
            }
        } while ($child_item);

        return array($item, $dividers_list);
    }

    public function themeAboutAction()
    {
        $app_id = $this->getAppId();
        $app = wa()->getAppInfo($app_id);
        $theme_id = waRequest::get('theme');
        $theme = new waTheme($theme_id, $app_id);

        $this->setTemplate('ThemeAbout.html', true);

        $this->display(array(
            'design_url' => $this->design_url,
            'app_id'     => $app_id,
            'app'        => $app,
            'theme_id'   => $theme_id,
            'theme'      => $theme,
            'options'    => $this->options,
        ));
    }

    public function themesAction()
    {
        $app_id = $this->getAppId();
        $app = wa()->getAppInfo($app_id);

        $used_app_themes = [];
        $app_themes = wa()->getThemes($app_id);
        $app_routes = wa()->getRouting()->getByApp($app_id);
        $route_themes = ['theme', 'theme_mobile'];
        foreach ($app_routes as $domain => $domain_routes) {
            foreach ($domain_routes as $route) {
                foreach ($route_themes as $route_theme_key) {
                    $route_theme = ifempty($route, $route_theme_key, null);
                    if (!empty($route_theme) && !empty($app_themes[$route_theme])) {
                        $used_app_themes[] = $route_theme;
                    }
                }
            }
        }
        $used_app_themes = array_unique($used_app_themes);

        $this->setTemplate('Themes.html', true);

        $this->display(array(
            'routes'          => $this->getRoutes(),
            'domains'         => wa()->getRouting()->getDomains(),
            'design_url'      => $this->design_url,
            'themes_url'      => $this->themes_url,
            'template_path'   => $this->getConfig()->getRootPath().'/wa-system/design/templates/',
            'app_id'          => $app_id,
            'app'             => $app,
            'app_themes'      => $app_themes,
            'used_app_themes' => $used_app_themes,
            'options'         => $this->options,
        ));
    }

    public function themeSettingsAction()
    {
        try {
            $theme_id = waRequest::get('theme');
            $theme = new waTheme($theme_id);
            if ($theme->parent_theme && waRequest::post('parent_settings')) {
                $this->saveThemeSettings($theme->parent_theme, waRequest::post('parent_settings'), waRequest::file('parent_image'), waRequest::post('locale'));
            }
            $this->saveThemeSettings($theme, waRequest::post('settings', array(), 'array'), waRequest::file('image'), waRequest::post('locale'));
            $this->displayJson(array());
        } catch (waException $e) {
            $this->displayJson(array(), $e->getMessage());
        }
    }

    /**
     * @param waTheme $theme
     * @param array $settings
     * @param waRequestFileIterator $files
     * @throws waException
     */
    protected function saveThemeSettings(waTheme $theme, $settings, $files, $locale = null)
    {
        if ($theme->type == waTheme::ORIGINAL) {
            $theme->copy();
        }
        $old_settings = $theme['settings'];
        $edition = false;
        foreach ($files as $k => $f) {
            /**
             * @var waRequestFile $f
             */
            if (isset($old_settings[$k])) {
                $error = '';
                $filename = str_replace('*', $f->extension, $old_settings[$k]['filename']);
                if ($this->uploadImage($f, $theme->path.'/'.$filename, $error)) {
                    $settings[$k] = $filename. '?v'. time();
                    $edition = true;
                } elseif ($error) {
                    throw new waException($error);
                }
            }
        }
        if ($edition) {
            $theme['edition'] = true;
        }
        $theme['settings'] = $settings;
        $theme->save();
    }

    /**
     * @param waRequestFile $f
     * @param string $path
     * @param string $error
     * @return bool
     */
    protected function uploadImage(waRequestFile $f, $path, &$error = '')
    {
        if ($f->uploaded()) {
            if (!$this->isImageValid($f, $error)) {
                return false;
            }
            $path = str_replace('*', $f->extension, $path);
            if (!$f->moveTo($path)) {
                $error = sprintf(_w('Failed to upload file %s.'), $f->name);
                return false;
            }
            return true;
        } else {
            if ($f->name) {
                $error = sprintf(_w('Failed to upload file %s.'), $f->name).' ('.$f->error.')';
            }
            return false;
        }
    }

    /**
     * @param waRequestFile $f
     * @param string $error
     * @return bool
     */
    protected function isImageValid(waRequestFile $f, &$error = '')
    {
        // If you add svg here, then on sites with cdn such pictures will not be loaded.
        // Design themes must use the $wa_real_theme_url variable for settings with the image type.
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array(strtolower($f->extension), $allowed)) {
            $error = sprintf(_ws("Files with extensions %s are allowed only."), '*.'.implode(', *.', $allowed));
            return false;
        }
        return true;
    }

    public function themeDownloadAction()
    {
        $theme_id = waRequest::get('theme');
        $app_id = waRequest::get('app_id', $this->getAppId());
        $theme = new waTheme($theme_id, $app_id);
        if ($theme['type'] == waTheme::TRIAL) {
            throw new waException('Access denied', 403);
        }
        $this->logAction('theme_download', $theme_id);
        $target_file = $theme->compress(wa()->getTempPath("themes"));
        waFiles::readFile($target_file, basename($target_file), false);
        try {
            waFiles::delete($target_file);
        } catch (Exception $ex) {

        }
    }

    public function themeExportSettingsAction()
    {
        $theme_id = waRequest::get('theme');
        $app_id = waRequest::get('app_id', $this->getAppId());
        $theme = new waTheme($theme_id, $app_id);
        $incorrect_settings = $this->checkExistenceSettings($theme);
        if ($incorrect_settings === false) {
            $target_file = $theme->compressSettings(wa()->getTempPath("themes"));
            waFiles::readFile($target_file, basename($target_file), false);
            try {
                waFiles::delete($target_file);
            } catch (Exception $ex) {

            }
        } else {
            return $this->displayJson(array(), $incorrect_settings);
        }
    }

    public function themeImportSettingsAction()
    {
        $theme_id = waRequest::get('theme');
        $app_id = waRequest::get('app_id', $this->getAppId());
        $theme = new waTheme($theme_id, $app_id);

        if ($archive = waRequest::file('theme_settings')) {
            $archive_extension = pathinfo($archive->name, PATHINFO_EXTENSION);
            if ($archive_extension !== 'gz') {
                try {
                    waFiles::delete($archive->tmp_name);
                } catch (waException $e) {

                }
                return $this->displayJson(array(), _ws('Invalid archive'));
            }

            /**
             * @var waRequestFile
             */
            if ($archive->uploaded()) {
                $archive_path = wa()->getDataPath('design/settings/import/').$archive->name;
                $archive->moveTo($archive_path);
                try {
                    $theme->extractSettings($archive_path);
                    //$this->logAction('theme_upload', $theme->id);
                    $this->displayJson(array('theme' => $theme_id));
                } catch (Exception $e) {
                    $this->displayJson(array(), $e->getMessage());
                }
            } else {
                $message = $archive->error;
                if (!$message) {
                    $message = 'Error while file upload';
                }
                $this->displayJson(array(), $message);
            }
        } else {
            $this->displayJson(array(), 'Error while file upload');
        }
    }

    protected function themeRenameAction()
    {
        try {
            $theme = new waTheme(waRequest::post('theme'));
            if ($theme['type'] == waTheme::TRIAL) {
                throw new waException('Access denied', 403);
            }
            $id = $theme->move(waRequest::post('id'), array(
                'name' => waRequest::post('name')
            ))->id;
            $this->logAction('theme_rename');
            $this->displayJson(array('redirect' => "{$this->design_url}theme={$id}&action=theme"));
        } catch (waException $e) {
            $this->displayJson(array(), $e->getMessage());
        }
    }

    public function themeParentAction()
    {
        try {
            if ($id = waRequest::post('id')) {
                $theme = new waTheme($id);
                if ($theme['type'] == waTheme::TRIAL) {
                    throw new waException('Access denied', 403);
                }
                $theme->parent_theme_id = waRequest::post('parent_theme_id');
                $theme->save();
                $this->displayJson(array('parent_theme_id' => $theme->parent_theme_id));
            }

        } catch (waException $e) {
            $this->displayJson(array(), $e->getMessage());
        }
    }

    public function themeCopyAction()
    {
        try {
            $theme = new waTheme(waRequest::post('theme'));
            if ($theme['type'] == waTheme::TRIAL) {
                throw new waException('Access denied', 403);
            }
            $duplicate = $theme->duplicate(!!waRequest::post('related'), (array)waRequest::post('options'));
            $this->logAction('theme_duplicate', $theme->id);
            $data = array(
                'redirect' => "{$this->design_url}theme={$duplicate->id}&action=theme",
            );
            $this->displayJson($data);
        } catch (Exception $e) {
            $this->displayJson(array(), $e->getMessage());
        }
    }

    public function revertFileAction()
    {
        try {
            $theme = new waTheme(waRequest::post('theme'));
            if ($theme['type'] == waTheme::TRIAL) {
                throw new waException('Access denied', 403);
            }
            $file = waRequest::post('file');
            $theme->revertFile($file);
            $this->displayJson(array());
        } catch (waException $e) {
            $this->displayJson(array(), $e->getMessage());
        }
    }

    public function themeResetAction()
    {
        try {
            $theme = new waTheme(waRequest::post('theme'));
            if ($theme['type'] == waTheme::TRIAL) {
                throw new waException('Access denied', 403);
            }
            $parent = $theme->parent_theme;
            $theme->brush();
            // reset parent theme
            if ($parent && waRequest::post('parent')) {
                $parent->brush();
            }
            $this->logAction('theme_reset', $theme->id);
            $this->displayJson(array());
        } catch (waException $e) {
            $this->displayJson(array(), $e->getMessage());
        }
    }

    public function themeDeleteAction()
    {
        try {
            $theme_id = waRequest::post('theme');
            $theme = new waTheme($theme_id);

            if (wa()->appExists('installer')) {
                wa('installer');
                $sender = new installerUpdateFact(installerUpdateFact::ACTION_DEL, array($this->getAppId().'/themes/'.$theme_id));
                $sender->query();
            }

            $theme->purge();
            $this->logAction('theme_delete', $theme_id);
            $this->displayJson(array('redirect' => $this->design_url, 'theme_id' => $theme_id));
        } catch (waException $e) {
            $this->displayJson(array(), $e->getMessage());
        }
    }

    protected function themeUploadAction()
    {
        if ($file = waRequest::file('theme_files')) {
            /**
             * @var waRequestFile
             */
            if ($file->uploaded()) {
                try {
                    $theme = waTheme::extract($file->tmp_name);
                    $this->logAction('theme_upload', $theme->id);
                    $this->displayJson(array('theme' => $theme->id));
                } catch (Exception $e) {
                    waFiles::delete($file->tmp_name);
                    $this->displayJson(array(), $e->getMessage());
                }
            } else {
                $message = $file->error;
                if (!$message) {
                    $message = 'Error while file upload';
                }
                $this->displayJson(array(), $message);
            }
        } else {
            $this->displayJson(array(), 'Error while file upload');
        }
    }

    public function viewOriginalAction()
    {
        $theme_id = waRequest::get('theme_id');

        $file = array();
        if ($theme_id && $f = waRequest::get('file')) {
            $app_id = $this->getAppId();
            $theme = new waTheme($theme_id, $app_id);
            $file = $theme->getFile($f);
            if ($file['parent']) {
                $theme = $theme->parent_theme;
                $theme_id = $theme->id;
                $app_id = $theme->app_id;
            }
            $theme_path = wa()->getAppPath('themes/'.$theme_id, $app_id).'/';
            $path = $theme_path.$f;
            if ($theme['type'] == waTheme::OVERRIDDEN && file_exists($path)) {
                $file = $theme->getFile($f);
                $file['id'] = $f;
                $file['content'] = file_get_contents($path);
                $file['theme_path'] = str_replace(wa()->getConfig()->getRootPath(), '', $theme_path);
            }
        }

        $this->setTemplate('DesignViewOriginal.html', true);

        $this->display(array('file' => $file));
    }

    public function getDesignUrl()
    {
        return $this->design_url;
    }

    protected function getView()
    {
        return wa('webasyst')->getView();
    }

    /**
     * Get default dir of templates for these actions
     * @inheritDoc
     */
    protected function getTemplateDir()
    {
        return $this->getConfig()->getRootPath().'/wa-system/design/templates/';
    }

    /**
     * Get default dir of lagacy templates of these actions
     * @inheritDoc
     */
    protected function getLegacyTemplateDir()
    {
        return $this->getConfig()->getRootPath().'/wa-system/design/templates-legacy/';
    }

    public function checkExistenceSettings($theme)
    {
        $theme_settings = $theme->getSettings();
        if (empty($theme_settings)) {
            $parent_theme = explode(':', $theme->parent_theme_id);
            $theme_id = ifset($parent_theme[1], null);

            $app_name = $app_url = null;
            if (!empty($parent_theme[0])) {
                $app_id = $parent_theme[0];
                $app_name = wa($app_id)->getConfig()->getName();
                $app_url = $this->getAppAppearanceUrl($app_id);
                if (!empty($theme_id)) {
                    $app_url .= '/theme=' . $theme_id;
                }
            }
            $response = array(
                'message' => _ws('This design theme does not have its own settings. Try to export the parent theme’s settings.'),
                'app_name' => _ws($app_name),
                'appearance_name' => _ws('Appearance'),
                'app_url' => $app_url,
                'theme_id' => $theme_id
            );
            return $response;
        }

        return false;
    }

    public function getAppAppearanceUrl($application_id = 'site')
    {
        $app_url = wa($application_id)->getAppUrl($application_id);
        switch ($application_id) {
            case 'blog':
                $app_url .= '?module=design';
                break;
            case 'shop':
                $app_url .= '?action=storefronts#/design';
                break;
            default:
                $app_url .= '#/design';
                break;
        }

        return $app_url;
    }

    /**
     * @param string $theme_id
     * @return bool
     * @throws waException
     */
    private function needShowReviewWidget($theme_id)
    {
        return wa()->appExists('installer') && $theme_id != 'default';
    }
}
