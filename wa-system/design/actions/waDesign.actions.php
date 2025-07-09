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

        if (waRequest::get('onlyThemeList')) {
            $this->setTemplate('ThemesList.html', true);

            $this->getView()->assign([
                'current_url'             => $current_url,
                'design_url'              => $this->design_url,
                'themes'                  => $themes,
                'themes_routes'           => $themes_routes,
            ]);
            /* костыль с displayJson ибо через display в приложении Блог вываливается весь лайаут и экран уходит в вечную перезагрузку */
            $this->displayJson(['html' => $this->getView()->fetch($this->getTemplate())]);
        } else {
            $this->setTemplate('Design.html', true);

            $this->display([
                'current_url'             => $current_url,
                'design_url'              => $this->design_url,
                'themes_url'              => $this->themes_url,
                'theme'                   => $theme,
                'route'                   => $route,
                'themes'                  => $themes,
                'themes_routes'           => $themes_routes,
                'app_id'                  => $app_id,
                'app'                     => $app,
                'routing_url'             => $routing_url,
                'options'                 => $this->options,
                'need_show_review_widget' => $this->needShowReviewWidget($t_id),
                'edit_data'               => $this->getThemesEditData(['theme' => $t_id])
            ]);
        }

    }

    public function editAction()
    {
        $get = waRequest::get();

        $data = $this->getThemesEditData($get);
        $this->setTemplate('DesignEdit.html', true);
        $this->display($data);
    }

    public function editFilesAction()
    {
        $get = waRequest::get();

        $data = $this->getThemesEditData($get);
        $this->setTemplate('DesignEditFiles.html', true);
        $this->display($data);
    }

    protected function getThemesEditData(array $get = []) {
        $app_id = $this->getAppId();
        $app = wa()->getAppInfo($app_id);
        $theme_id = ifset($get, 'theme', '');
        $get_file = ifset($get, 'file', null);
        $get_domain = ifset($get, 'domain', '');
        $get_route = ifset($get, 'route', '');

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
        if ($get_file !== '') {
            if (!$get_file) {
                $files = $theme['files'];
                if (isset($files['index.html'])) {
                    $f = 'index.html';
                } else {
                    ksort($files);
                    $f = key($files);
                }
            }
            $file = $theme->getFile($get_file);
            if (!$file) {
                $get_file = preg_replace('@(\\{1,}|/{2,})@', '/', ifempty($get_file, ''));
                if (!$get_file
                    ||
                    preg_match('@(^|/)\.\./@', $get_file)
                    ||
                    !file_exists($theme->getPath().'/'.$get_file)
                ) {
                    $get_file = 'index.html';
                    $file = $theme->getFile($get_file);
                }
            }
            $file['id'] = $get_file;
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
                $parent_file = $theme->parent_theme->getFile($get_file);
                if (empty($file['description'])) {
                    $file['description'] = ifset($parent_file['description'], '');
                }
                if (!empty($parent_file['modified'])) {
                    $file['modified'] = $parent_file['modified'];
                }
                if ($theme->parent_theme->type == waTheme::OVERRIDDEN) {
                    $file['has_original'] = $theme->parent_theme['type'] == file_exists(wa()->getAppPath('themes/'.$theme->parent_theme->id, $theme->parent_theme->app_id).'/'.$get_file);
                }
            } else {
                $path = $theme->getPath();
                if ($theme->type == waTheme::OVERRIDDEN) {
                    $file['has_original'] = $theme['type'] == file_exists(wa()->getAppPath('themes/'.$theme_id, $app_id).'/'.$get_file);
                }
            }
            $path .= '/'.$get_file;
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
            if ($r['theme'] == $theme_id && $r['_domain'] != $get_domain && $r['_id'] != $get_route) {
                $theme_usages[] = htmlspecialchars($r['_domain'].'/'.$r['url']);
                $theme_usages_decoded[] = $idna->decode(htmlspecialchars($r['_domain'].'/'.$r['url']));
            }
        }

        $route_url = false;
        $route_url_decoded = null;
        if ($get_domain) {
            $domain_routes = wa()->getRouting()->getByApp(wa()->getApp(), $get_domain);
            if (isset($domain_routes[$get_route])) {
                $route_url = htmlspecialchars($get_domain.'/'.$domain_routes[$get_route]['url']);
                $route_url_decoded = $idna->decode($route_url);
            }
        }

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

        return $data;
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

        $same_domain = true;
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
            if (!$preview_url) {
                $preview_url = $url;
                $same_domain = wa()->getRouting()->getDomain() == $route['_domain'];
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
                if ($preview_url && !$same_domain) {
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

    protected function hasRouteAnyApp($theme_id)
    {
        foreach (wa()->getRouting()->getAllRoutes() as $domain => $routes) {
            if (!is_array($routes)) {
                continue;
            }
            foreach ($routes as $route_id => $r) {
                if ((ifset($r, 'theme', null) === $theme_id) || (ifset($r, 'theme_mobile', null) === $theme_id)) {
                    return true;
                }
            }
        }
        return false;
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
                        if (isset($theme['files'][$file])) {
                            $errors = _ws('A file with this name already exists. Please enter a different file name.');
                        } else {
                            $theme->addFile($file, waRequest::post('description'));
                        }
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
                $this->displayJson(array(), sprintf(_ws('Settings could not be saved due to insufficient write permissions for file %s.'), 'wa-config/routing.php'));
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
        } else if (wa()->whichUI() !== '1.3') {
            $domain = waRequest::post('domain');
            $route_id = null;
            foreach ($routes[$domain] as $_id => $route) {
                if ($route['url'] === '*') {
                    $route_id = $_id;
                    break;
                }
            }
            $selected_routes = waRequest::post('routes', [], waRequest::TYPE_ARRAY);
            if ($selected_routes) {
                foreach ($selected_routes as $_id => $on) {
                    if ($on && isset($routes[$domain][$_id])) {
                        if (!waRequest::post('mobile_only')) {
                            $routes[$domain][$_id]['theme'] = $theme_id;
                        }
                        $routes[$domain][$_id]['theme_mobile'] = $theme_id;
                    }
                }
            }

            $selected_blockpages = waRequest::post('blockpages', [], waRequest::TYPE_ARRAY);
            if ($selected_blockpages && wa()->appExists('site')) {
                wa('site');
                $selected_blockpages = array_keys(array_filter($selected_blockpages));
                $domain_id_by_name = array_flip(siteHelper::getDomains());
                if (isset($domain_id_by_name[$domain]) && class_exists('siteBlockpageModel')) {
                    $blockpage_model = new siteBlockpageModel();
                    $blockpage_model->updateByField([
                        'domain_id' => $domain_id_by_name[$domain],
                        'id' => $selected_blockpages,
                    ], [
                        'theme' => $theme_id,
                    ]);
                    $blockpage_model->updateByField([
                        'domain_id' => $domain_id_by_name[$domain],
                        'final_page_id' => $selected_blockpages,
                    ], [
                        'theme' => $theme_id,
                    ]);
                }
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
        $all_child_themes = [];
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
                    if ($theme->parent_theme_id) {
                        $all_child_themes[$theme_app_id][$id] = [
                            'app_name' => $app['name'],
                            'name' => $theme->name,
                            'parent_theme_id' => $theme->parent_theme_id
                        ];
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
            $domain = wa()->getConfig()->getDomain();
            foreach ($routes as $r_id => $r) {
                if (ifset($r, 'app', '') === 'site' && !empty($r['site_tech_route'])) {
                    unset($routes[$r_id]);
                    continue;
                }
                if ((waRequest::get('route') == $r['_id']) && !empty($r['locale'])) {
                    $current_locale = $r['locale'];
                }
                if (!$preview_url && $r['app'] == $app_id) {
                    $preview_url = $r['_url'];
                    if ($r['_domain'] !== $domain) {
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
            $parent_theme_id = $app_id . ':' . $theme_id;
            $child_themes = [];
            if (empty($current_theme->parent_theme_id)) {
                foreach ($all_child_themes as $child_theme_app_id => $themes) {
                    foreach ($themes as $theme_info) {
                        if ($theme_info['parent_theme_id'] == $parent_theme_id) {
                            $child_themes[$child_theme_app_id] = $theme_info;
                        }
                    }
                }
            }

            $cover = false;
            if (file_exists($current_theme->getPath().'/cover.png')) {
                $cover = $current_theme->getUrl().'cover.png';
            } elseif ($current_theme->parent_theme && file_exists($current_theme->parent_theme->getPath().'/cover.png')) {
                $cover = $current_theme->parent_theme->getUrl().'cover.png';
            }

            $route_url = false;
            $_d = waRequest::get('domain');
            if ($_d) {
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

            $only_settings = waRequest::get('onlySettings');
            $domains = wa()->getRouting()->getDomains();
            $apps = wa()->getApps();

            // for UI 2.0
            $sitemap_app_ids = $this->getSitemapAppIds();
            $settlements_by_domain = [];
            $has_theme_usage = false;
            if (wa()->whichUI() != '1.3') {
                $all_blockpages = $this->getBlockpagesByDomain($domains, ifset($parent_themes, 'site', 'themes', []), $current_theme->id);
                foreach ($domains as $_domain) {
                    list($settlements, $has_not_support_theme) = $this->workupSettlements($apps, $_domain, $parent_themes, $current_theme->id);

                    foreach ($settlements as $s) {
                        $condition_theme_usage = $_d ? $_domain === $_d : $s['app']['id'] === $app_id;
                        if (!$condition_theme_usage) {
                            continue;
                        }
                        if ($s['theme'] === $current_theme->id || $s['theme_mobile'] === $current_theme->id) {
                            $has_theme_usage = true;
                            break;
                        }
                    }

                    $settlements_by_domain[$_domain]['has_not_support_theme'] = $has_not_support_theme;
                    $settlements_by_domain[$_domain]['blockpages'] = ifset($all_blockpages, $_domain, []);

                    foreach ($settlements as $s) {
                        if (ifset($s, 'app', 'id', '') === 'site' && !empty($s['site_tech_route'])) {
                            continue;
                        }
                        if (isset($s['app']['icon'])) {
                            $s['app'] = [
                                'id' => $s['app']['id'],
                                'icon' => $s['app']['icon'],
                                'disabled' => ifset($s['app'], 'disabled', false),
                            ];
                        }

                        if (ifset($s, 'url', null) === '*') {
                            $settlements_by_domain[$_domain]['main_page'] = $s;
                            $settlements_by_domain[$_domain]['main_page']['page_type'] = 'route';
                            continue;
                        }

                        if (in_array($s['app']['id'], $sitemap_app_ids)) {
                            $settlements_by_domain[$_domain]['sitemap_apps'][] = $s;
                        } else {
                            $settlements_by_domain[$_domain]['settings_apps'][] = $s;
                        }
                    }

                    foreach ($settlements_by_domain[$_domain]['blockpages'] as $i => $bp) {
                        if ($bp['url_formatted'] === '/') {
                            $settlements_by_domain[$_domain]['main_page'] = $bp;
                            $settlements_by_domain[$_domain]['main_page']['page_type'] = 'blockpage';
                            unset($settlements_by_domain[$_domain]['blockpages'][$i]);
                            $settlements_by_domain[$_domain]['blockpages'] = array_values($settlements_by_domain[$_domain]['blockpages']);
                            break;
                        }
                    }
                }
            }

            $this->setTemplate('Theme.html', true);

            $this->display([
                'current_locale'                      => $current_locale,
                'routes'                              => $routes,
                'domains'                             => $domains,
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
                'has_theme_usage_any_app'             => $this->hasRouteAnyApp($theme_id),
                'child_themes'                        => $child_themes,
                'path'                                => waTheme::getThemesPath($app_id),
                'cover'                               => $cover,
                'route_url'                           => $route_url,
                'apps'                                => $apps,
                'need_show_review_widget'             => $this->needShowReviewWidget($theme_id),
                'only_settings'                       => $only_settings,
                'current_domain'                      => $_d,
                'settlements_by_domain'               => $settlements_by_domain,
                'has_theme_usage'                     => $has_theme_usage,
            ]);
        }
    }

    protected function getSitemapAppIds()
    {
        $result = [];
        foreach (wa()->getApps() as $app) {
            if (empty($app['frontend']) || empty($app['themes'])) {
                continue;
            }
            if (!empty($app['routing_params']['private'])) {
                continue;
            }
            $result[] = $app['id'];
        }
        return $result;
    }

    protected function workupSettlements(array $apps, string $domain, array $parent_themes, string $current_theme_id)
    {
        $routes_app_id_to_alias = [
            'mailer' => _ws('My account › My subscriptions'),
        ];
        $routes = wa()->getRouting()->getRoutes($domain);

        foreach ($apps as $app_id => $app) {
            if (empty($app['id']) || empty($app['themes'])) {
                unset($apps[$app_id]);
            }
        }

        $page_route_urls = [];
        $app_pages_search = [];
        foreach ($routes as $route_id => &$route) {
            if (
                !isset($route['app']) ||
                $route['app'] === ':text' ||
                !isset($apps[$route['app']]) ||
                !empty($route['redirect'])
            ) {
                unset($routes[$route_id]);
                continue;
            }

            if (ifset($route, 'theme', '') === '') {
                $route['theme'] = 'default';
            }

            $app_id = $route['app'];
            $route['route_id'] = $route_id;
            $route['url_formatted'] = '/'.ltrim(rtrim($route['url'], '*'), '/');
            $route['app'] = ifempty($apps, $app_id, [
                'id' => $route['app'],
                'disabled' => true,
            ]);

            // We need to fetch all top-level pages of all settlements we loop over.
            // Each app has a different page model to fetch data from.
            // Here we group settlements by app in order to minimize number of SQL queries.
            if (empty($route['app']['disabled']) && wa()->appExists($app_id)) {
                try {
                    if (!isset($app_pages_search[$app_id])) {
                        wa($app_id);
                        $app_page_model_class = $app_id.'PageModel';
                        if (class_exists($app_page_model_class)) {
                            $app_page_model = new $app_page_model_class();
                            if ($app_id == 'site') {
                                if (!isset($domain_id_by_name)) {
                                    $domain_id_by_name = array_flip(siteHelper::getDomains());
                                }
                                if (isset($domain_id_by_name[$domain])) {
                                    $app_pages_search[$app_id] = [$app_page_model, [
                                        'domain_id' => $domain_id_by_name[$domain],
                                        'route' => [],
                                    ]];
                                }
                            } else {
                                $app_pages_search[$app_id] = [$app_page_model, [
                                    'domain' => $domain,
                                    'route' => [],
                                ]];
                            }
                        }
                    }

                    if (isset($app_pages_search[$app_id])) {
                        $app_pages_search[$app_id][1]['route'][] = $route['url'];
                        $page_route_urls[$route['url']] = $route_id;
                    }
                } catch (waException $e) {}
            }

            $route['pages'] = [];
            if (empty($route['_name'])) {
                if (!empty($route['app']['name'])) {
                    $route['_name'] = $route['app']['name'];
                } else {
                    $route['_name'] = $route['app']['id'];
                }
            }
        }
        unset($route);

        // Fetch all pages from DB
        foreach ($app_pages_search as $app_id => $_) {
            list($model, $search) = $_;
            try {
                $pages = $model->getByField($search, 'id');
            } catch (waException $e) {
                $pages = [];
            }
            if ($pages) {
                $pages = array_map(function($p) use ($routes, $page_route_urls) {
                    if (!isset($page_route_urls[$p['route']])) {
                        return null;
                    }
                    $route_id = $page_route_urls[$p['route']];
                    return [
                        'id' => $p['id'],
                        'name' => $p['name'],
                        'status' => $p['status'],
                        'url_formatted' => $routes[$route_id]['url_formatted'].$p['full_url'],
                        'parent_id' => $p['parent_id'],
                        'full_url' => $p['full_url'],
                        'route' => $p['route'],
                        'sort' => $p['sort'],
                        'children' => [],
                    ];
                }, $pages);
                $pages = self::formatPagesTree(array_filter($pages));

                $main_page_children = [];
                foreach ($pages as $p) {
                    $route_id = $page_route_urls[$p['route']];
                    if (empty($routes[$route_id]['pages']) && ($p['full_url'] === '' || $p['full_url'] === '/')) {
                        if ($p['name']) {
                            $routes[$route_id]['_name'] = $p['name'];
                        }
                        $main_page_children = $p['children'];
                    } else {
                        $routes[$route_id]['pages'][] = $p;
                    }
                }

                foreach ($main_page_children as $p) {
                    $route_id = $page_route_urls[$p['route']];
                    $routes[$route_id]['pages'][] = $p;
                }
            }
        }

        $has_not_support_theme = false;
        foreach ($routes as &$r) {
            $r['theme_mobile'] = ifset($r, 'theme_mobile', $r['theme']);
            $r['used_theme'] = $current_theme_id === $r['theme'];
            $r['used_theme_mobile'] = $current_theme_id === $r['theme_mobile'];

            if (!isset($parent_themes[$r['app']['id']])) {
                continue;
            }
            $available_themes = $parent_themes[$r['app']['id']]['themes'];
            if (!$available_themes) {
                continue;
            }

            if (!isset($available_themes[$current_theme_id])) {
                $r['theme_not_supported'] = true;
                $has_not_support_theme = true;
            }

            $r['theme_names'][] = $available_themes[$r['theme']] ?? '';
            $r['theme_names'][] = $available_themes[$r['theme_mobile']] ?? '';
            $r['theme_names'] = array_unique($r['theme_names']);

            // replace route name
            if ($routes_app_id_to_alias && isset($routes_app_id_to_alias[$r['app']['id']])) {
                $r['_name'] = $routes_app_id_to_alias[$r['app']['id']];
            }
        }
        unset($r);

        $settlements = array_reverse(array_values($routes));
        $apps_to_end = [
            'mailer' => 1,
        ];
        usort($settlements, function ($s1, $s2) use ($apps_to_end) {
            $has_s1 = isset($apps_to_end[$s1['app']['id']]);
            $has_s2 = isset($apps_to_end[$s2['app']['id']]);
            if ($has_s1 && !$has_s2) return 1;
            if (!$has_s1 && $has_s2) return -1;
            return 0;
        });

        return [$settlements, $has_not_support_theme];
    }

    protected function getBlockpagesByDomain($domains, $available_themes, $current_theme_id)
    {
        if (!wa()->appExists('site')) {
            return [];
        }
        wa('site');
        if (!class_exists('siteBlockpageModel')) {
            return [];
        }

        $idna = new waIdna();
        $domains = array_flip($domains);
        foreach (array_keys($domains) as $d) {
            $domains[$idna->decode($d)] = 1;
        }

        $domain_id_by_name = array_flip(siteHelper::getDomains());
        $domain_id_by_name = array_intersect_key($domain_id_by_name, $domains);
        $blockpage_model = new siteBlockpageModel();
        $blockpages = $blockpage_model->getByField([
            'domain_id' => array_values($domain_id_by_name),
            'final_page_id' => null,
        ], true);

        $result = [];
        $domains = array_flip($domain_id_by_name);
        foreach($blockpages as $p) {
            $d = $domains[$p['domain_id']];
            $full_url = '/'.trim($p['full_url'], '/');
            if ($full_url !== '/') {
                $full_url .= '/';
            }
            $theme = ifset($p, 'theme', 'default');
            $theme_names = [ifset($available_themes, $theme, ifset($available_themes, 'default', ''))];

            $result[$d][$p['id']] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'status' => $p['status'] == 'final_published' ? 1 : 0,
                'url_formatted' =>  $full_url,
                'theme' => $theme,
                'theme_names' => $theme_names,
                'used_theme' => $theme === $current_theme_id,
                'parent_id' => $p['parent_id'],
                'sort' => $p['sort'],
                'children' => [],
            ];
        }

        foreach ($result as $d => &$pages) {
            $pages = self::formatPagesTree($pages);
        }
        unset($pages);

        return $result;
    }

    protected static function formatPagesTree($pages)
    {
        uasort($pages, function($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });

        $result = [];
        foreach ($pages as $id => &$p) {
            unset($p['sort']);
            if (!$p['parent_id'] || !isset($pages[$p['parent_id']])) {
                $result[] =& $p;
            } else {
                $pages[$p['parent_id']]['children'][] =& $p;
            }
        }
        unset($p);

        return $result;
    }

    /**
     * Convert flat list of theme settings into hierarchical tree structure
     * based on group divider levels.
     *
     * Each group_divider is allowed to have several "normal" settings at the beginning
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
                    throw new waException('Level is not set for', $item);
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

        // Extract items from the beginning of $dividers_list
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
        $app_themes = wa()->getThemes($app_id, true);
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
        if (!empty($app['themes'])) {
            $used_app_themes[] = 'default';
        }
        $used_app_themes = array_unique($used_app_themes);

        $all_domains = wa()->getRouting()->getDomains();
        $used_apps_themes = [];
        foreach($all_domains as $d) {
            foreach(wa()->getRouting()->getRoutes($d) as $route_id => $route) {
                if (is_array($route) && isset($route['app']) && $route['app'] !== $app_id) {
                    foreach($route_themes as $k) {
                        if (isset($route[$k])) {
                            $used_apps_themes[$route[$k]] = true;
                        }
                    }
                }
            }
        }

        $this->setTemplate('Themes.html', true);

        $this->display(array(
            'routes'          => $this->getRoutes(),
            'domains'         => $all_domains,
            'design_url'      => $this->design_url,
            'themes_url'      => $this->themes_url,
            'template_path'   => $this->getConfig()->getRootPath().'/wa-system/design/templates/',
            'app_id'          => $app_id,
            'app'             => $app,
            'app_themes'      => $app_themes,
            'used_app_themes' => $used_app_themes,
            'options'         => $this->options,
            'used_apps_themes' => $used_apps_themes,
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
                $error = sprintf(_ws('Failed to upload file %s.'), $f->name);
                return false;
            }
            return true;
        } else {
            if ($f->name) {
                $error = sprintf(_ws('Failed to upload file %s.'), $f->name).' ('.$f->error.')';
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
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
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
            if ($theme['type'] == waTheme::ORIGINAL) {
                $theme->copy();
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
                    wa()->getConfig()->clearCache();
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
     * Get default dir of legacy templates of these actions
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
