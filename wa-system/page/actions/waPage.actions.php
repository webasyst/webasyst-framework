<?php

class waPageActions extends waActions
{
    protected $model;
    protected $sidebar = true;
    protected $url = '#/';
    protected $ibutton = true;

    protected $options = array(
        'container' => true,
        'show_url' => false,
        'save_panel' => true,
        'js' => array(
            'ace' => true,
            'editor' => true,
            'storage' => false
        ),
        'is_ajax' => false,
        'data' => array()
    );

    public function defaultAction()
    {
        $pages = $this->getPages();
        $data = array(
            'sidebar' => $this->sidebar,
            'page_url' => $this->url,
            'lang' => substr(wa()->getLocale(), 0, 2),
            'ibutton' => $this->ibutton,
            'options' => $this->options,
            'pages' => $pages
        );

        $routes = $this->getRoutes();
        foreach ($pages as $page) {
            $r = $page['domain'].'/'.$page['route'];
            if (!isset($routes[$r])) {
                $r = 0;
            }
            $routes[$r]['pages'][$page['id']] = $page;
        }
        if (isset($routes[0]) || !$routes) {
            $routes[0]['route'] = '';
            $routes[0]['domain'] = '';
        }
        foreach ($routes as &$r) {
            if (isset($r['pages'])) {
                $r['pages'] = $this->getPagesTree($r['pages']);
            }
        }

        $data['routes'] = $routes;

        $template = $this->getConfig()->getRootPath().'/wa-system/page/templates/Page.html';
        $this->display($this->prepareData($data), $template);
    }


    protected function settleAction()
    {
        $pages = $this->getPages();
        $routes = $this->getRoutes();
        $ids = array();
        foreach ($pages as $page) {
            $r = $page['domain'].'/'.$page['route'];
            if (!isset($routes[$r])) {
                $ids[] = $page['id'];
            }
        }
        $domain = waRequest::post('domain');
        $route = waRequest::post('route');
        if ($ids) {
            $this->getPageModel()->updateById($ids, array('domain' => $domain, 'route' => $route));
        }
        $this->displayJson(array());
    }


    protected function prepareData(&$data)
    {
        return $data;
    }


    protected function getPage($id)
    {
        return $this->getPageModel()->getById($id);
    }

    protected function editAction()
    {

        $id = waRequest::get('id');
        $page_model = $this->getPageModel();

        if (!$id || !($page = $this->getPage($id))) {
            $id = null;
            $page = array();
        }

        $url = '';
        if ($page) {
            $domain = $page['domain'];
            $route = $page['route'];
            if ($page['parent_id']) {
                $parent = $page_model->getById($page['parent_id']);
                $url = $parent['full_url'] ? rtrim($parent['full_url'], '/').'/' : '';
            }
        } else {
            if ($parent_id = waRequest::get('parent_id')) {
                $parent = $this->getPage($parent_id);
                $domain = $parent['domain'];
                $route = $parent['route'];
                $this->options['data']['info[parent_id]'] = $parent_id;
                $url = $parent['full_url'] ? rtrim($parent['full_url'], '/').'/' : '';
            } else {
                $domain = waRequest::get('domain');
                $this->options['data']['info[domain]'] = $domain;
                $route = waRequest::get('route');
                $this->options['data']['info[route]'] = $route;
            }
        }

        $routes = $this->getRoutes();
        if ($domain && isset($routes[$domain.'/'.$route])) {
            $url = 'http://'.$domain.'/'.wa()->getRouting()->clearUrl($route).$url;
        } else {
            $url = null;
        }

        $data = array(
            'url' => $url,
            'page' => $page,
            'page_url' => $this->url,
            'options' => $this->options,
            'preview_hash' => $this->getPreviewHash(),
            'lang' => substr(wa()->getLocale(), 0, 2),
            'ibutton' => $this->ibutton,
            'upload_url' => wa()->getDataUrl('img', true)
        ) + $this->getPageParams($id);

        $data['page_edit'] = wa()->event('page_edit', $data);

        $template = $this->getConfig()->getRootPath().'/wa-system/page/templates/PageEdit.html';
        $this->display($data, $template);
    }

    protected function getPagesTree($pages)
    {
        foreach ($pages as $page_id => $page) {
            if ($page['parent_id']) {
                $pages[$page['parent_id']]['childs'][] = &$pages[$page_id];
            }
        }

        foreach ($pages as $page_id => $page) {
            if (!empty($page['parent_id'])) {
                unset($pages[$page_id]);
            }
        }

        return $pages;
    }

    public static function printPagesTree($p, $pages, $prefix_url)
    {
        $html = '<ul class="menu-v with-icons" data-parent-id="'.$p['id'].'">';
        foreach ($pages as $page) {
            $html .= '<li class="drag-newposition"></li>';
            $html .= '<li class="dr" id="page-'.$page['id'].'">'.
            (!empty($page['childs']) ? '<i class="icon16 darr expander overhanging"></i>' : '').'<i class="icon16 notebook"></i>'.
            '<a class="wa-page-link" href="'.$prefix_url.$page['id'].'"><span class="count"><i class="icon10 add wa-page-add"></i></span>'.
            htmlspecialchars($page['name']).
            ' <span class="hint">/'.htmlspecialchars($page['full_url']).'</span>';
            if (!$page['status']) {
                $html .= ' <span class="wa-page-draft">'._ws('draft').'</span>';
            }
            $html .= '</a>';
            if (!empty($page['childs'])) {
                $html .= self::printPagesTree($page, $page['childs'], $prefix_url);
            }
            $html .= '</li>';
        }
        $html .= '<li class="drag-newposition"></li></ul>';
        return $html;
    }


    protected function getRoutes()
    {
        $routes = wa()->getRouting()->getByApp($this->getAppId());
        $result = array();
        foreach ($routes as $d => $domain_routes) {
            foreach ($domain_routes as $route) {
                $result[$d.'/'.$route['url']] = array(
                    'route' => $route['url'],
                    'domain' => $d
                );
            }
        }
        return $result;
    }


    protected function getPages()
    {
        return $this->getPageModel()->
            select('id,name,url,full_url,status,domain,route,parent_id')->order('parent_id,sort')->fetchAll('id');
    }


    /**
     * @param int $id - page id
     * @return array
     */
    protected function getPageParams($id)
    {
        $params = $other_params = array();
        $vars = array(
            'keywords' => _ws('META Keywords'),
            'description' => _ws('META Description')
        );

        if ($id) {
            $params = $this->getPageModel()->getParams($id);
        }

        $main_params = array();
        foreach ($vars as $v => $t) {
            if (isset($params[$v])) {
                $main_params[$v] = $params[$v];
                unset($params[$v]);
            } else {
                $main_params[$v] = '';
            }
        }
        return array(
            'vars' => $vars,
            'params' => $main_params,
            'other_params' => $params,
        );
    }

    protected function getPreviewHash()
    {
        $hash = $this->appSettings('preview_hash');
        if ($hash) {
            $hash_parts = explode('.', $hash);
            if (time() - $hash_parts[1] > 14400) {
                $hash = '';
            }
        }
        if (!$hash) {
            $hash = uniqid().'.'.time();
            $app_settings_model = new waAppSettingsModel();
            $app_settings_model->set($this->getAppId(), 'preview_hash', $hash);
        }

        return md5($hash);
    }

    public function saveAction()
    {
        $id = (int)waRequest::get('id');
        $data = waRequest::post('info');
        if (empty($data['name'])) {
            $data['name'] = '('._ws('no-title').')';
        }
        $data['url'] = ltrim($data['url'], '/');
        $data['status'] = isset($data['status']) ? 1 : 0;

        $page_model = $this->getPageModel();

        if ($id) {
            $is_new = false;
            $old = $page_model->getById($id);
            $data['full_url'] = substr($old['full_url'], 0, -strlen($old['url'])).$data['url'];
            if ($old['full_url'] && substr($old['full_url'], -1, 1) != '/') {
                $old['full_url'] .= '/';
            }
            // save to database
            if (!$page_model->update($id, $data)) {
                $this->displayJson(array(), _ws('Error saving web page'));
                return;
            }
            $this->logAction('page_edit', $id);
            $childs = $page_model->getChilds($id);
            if ($childs) {
                $page_model->updateFullUrl($childs, $data['full_url'], $old['full_url']);
            }
        } else {
            if (waRequest::post('translit') && !$data['url']) {
                $data['url'] = $this->translit($data['name']);
            }
            if ($data['url'] && substr($data['url'], -1) != '/' && strpos(substr($data['url'], -5), '.') === false) {
                $data['url'] .= '/';
            }
            if (isset($data['parent_id']) && $data['parent_id']) {
                $parent = $this->getPage($data['parent_id']);
                $data['full_url'] = ($parent['full_url'] ? rtrim($parent['full_url'], '/').'/' : '').$data['url'];
                $data['domain'] = $parent['domain'];
                $data['route'] = $parent['route'];
                $this->beforeSave($data, $parent);
            } else {
                $data['full_url'] = $data['url'];
                $this->beforeSave($data);
            }
            $is_new = true;
            if ($id = $page_model->add($data)) {
                $data['id'] = $id;
                $this->logAction('page_add', $id);
            } else {
                $this->displayJson(array(), _ws('Error saving web page'));
                return;
            }
        }

        // save params
        $this->saveParams($id);

        // prepare response
        $this->displayJson(array(
            'id' => $id,
            'name' => htmlspecialchars($data['name']),
            'add' => $is_new ? 1 : 0,
            'url' => $data['url'],
            'full_url' => $data['full_url'],
            'old_full_url' => isset($old) ? $old['full_url'] : '',
            'status' => $data['status']
        ));
    }


    public function getPageChilds($id)
    {
        $page_model = $this->getPageModel();
        $result = array();
        $ids = array($id);
        $sql = "SELECT id FROM ".$page_model->getTableName()." WHERE parent_id IN (i:ids)";
        while ($ids = $page_model->query($sql, array('ids' => $ids))->fetchAll(null, true)) {
            $result = array_merge($result, $ids);
        }
        return $result;
    }


    protected function beforeSave(&$data, $parent = array())
    {

    }

    public function translitAction()
    {
        $str = waRequest::post('str');
        $this->displayJson(array('str' => $this->translit($str)));
    }

    private function translit($str)
    {
        $str = preg_replace('/\s+/', '-', $str);
        if ($str) {
            foreach (waLocale::getAll() as $locale_id => $locale) {
                if ($locale_id != 'en_US') {
                    $str = waLocale::transliterate($str, $locale);
                }
            }
        }
        $str = preg_replace('/[^a-zA-Z0-9_-]+/', '', $str);
        return strtolower($str);
    }

    /**
     * @param int $id - page id
     */
    protected function saveParams($id)
    {
        $params = waRequest::post('params');
        $other_params = waRequest::post('other_params');
        if ($other_params) {
            $other_params = explode("\n", $other_params);
            foreach ($other_params as $param) {
                $param = explode("=", trim($param), 2);
                if (count($param) == 2) {
                    $params[$param[0]] = $param[1];
                }
            }
        }

        $this->getPageModel()->setParams($id, $params);
    }


    public function deleteAction()
    {
        $id = waRequest::post('id');
        $page_model = $this->getPageModel();
        $page = $page_model->getById($id);
        if ($page) {
            // remove childs
            $childs = $this->getPageChilds($id);
            if ($childs) {
                $page_model->delete($childs);
            }
            // remove from database
            $page_model->delete($id);
            $this->logAction('page_delete', $id);
        }
        $this->displayJson(array());
    }


    public function moveAction()
    {
        $page_model = $this->getPageModel();
        $parent_id = waRequest::post('parent_id');
        if (!$parent_id) {
            $parent_id = array(
                'domain' => waRequest::post('domain'),
                'route' => waRequest::post('route'),
            );
        }
        $page_id = waRequest::post('id', 0, 'int');
        $result = $page_model->move($page_id, $parent_id, waRequest::post('before_id', 0, 'int'));
        if ($result) {
            $this->logAction('page_move', $page_id);
        }
        $this->displayJson($result, $result ? null: _w('Database error'));
    }


    public function uploadimageAction()
    {
        $path = wa()->getDataPath('img', true);

        $response = '';

        if (!is_writable($path)) {
            $p = substr($path, strlen(wa()->getDataPath('', true)));
            $errors = sprintf(_w("File could not be saved due to insufficient write permissions for the %s folder."), $p);
        } else {
            $errors = array();
            $f = waRequest::file('file');
            $name = $f->name;
            if ($this->processFile($f, $path, $name, $errors)) {
                $response = wa()->getDataUrl('img/'.$name, true, null, !!waRequest::get('absolute'));
            }
            $errors = implode(" \r\n", $errors);
        }
        if (waRequest::get('filelink')) {
            $this->getResponse()->sendHeaders();
            if ($errors) {
                echo json_encode(array('error' => $errors));
            } else {
                echo json_encode(array('filelink' => $response));
            }
        } else {
            $this->displayJson($response, $errors);
        }
    }

    /**
     * @param waRequestFile $f
     * @param string $path
     * @param string $name
     * @param array $errors
     * @return bool
     */
    protected function processFile(waRequestFile $f, $path, &$name, &$errors = array())
    {
        if ($f->uploaded()) {
            if (!$this->isFileValid($f, $errors)) {
                return false;
            }
            if (!$this->saveFile($f, $path, $name)) {
                $errors[] = sprintf(_w('Failed to upload file %s.'), $f->name);
                return false;
            }
            return true;
        } else {
            $errors[] = sprintf(_w('Failed to upload file %s.'), $f->name).' ('.$f->error.')';
            return false;
        }
    }

    protected function isFileValid($f, &$errors = array())
    {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array(strtolower($f->extension), $allowed)) {
            $errors[] = sprintf(_ws("Files with extensions %s are allowed only."), '*.'.implode(', *.', $allowed));
            return false;
        }
        return true;
    }

    protected function saveFile(waRequestFile $f, $path, &$name)
    {
        $name = $f->name;
        if (!preg_match('//u', $name)) {
            $tmp_name = @iconv('windows-1251', 'utf-8//ignore', $name);
            if ($tmp_name) {
                $name = $tmp_name;
            }
        }
        if (file_exists($path.DIRECTORY_SEPARATOR.$name)) {
            $i = strrpos($name, '.');
            $ext = substr($name, $i + 1);
            $name = substr($name, 0, $i);
            $i = 1;
            while (file_exists($path.DIRECTORY_SEPARATOR.$name.'-'.$i.'.'.$ext)) {
                $i++;
            }
            $name = $name.'-'.$i.'.'.$ext;
        }
        return $f->moveTo($path, $name);
    }

    public function helpAction()
    {
        $wa_vars = array(
            '$wa_url' => _ws('URL of this Webasyst installation (relative)'),
            '$wa_app_url' => _ws('URL of the current app settlement (relative)'),
            '$wa_backend_url' => _ws('URL to access Webasyst backend (relative)'),
            '$wa_theme_url' => _ws('URL of the current app design theme folder (relative)'),

            '$wa->title()' => _ws('Title'),
            '$wa->title("<em>title</em>")' => _ws('Assigns a new title'),
            '$wa->accountName()' => _ws('Returns name of this Webasyst installation (name is specified in “Installer” app settings)'),
            '$wa->apps()' => _ws('Returns this site’s core navigation menu which is either set automatically or manually in the “Site settings” screen'),
            '$wa->currentUrl(bool <em>$absolute</em>)' => _ws('Returns current page URL (either absolute or relative)'),
            '$wa->domainUrl()' => _ws('Returns this domain’s root URL (absolute)'),
            '$wa->globals("<em>key</em>")' => _ws('Returns value of the global var by <em>key</em>. Global var array is initially empty, and can be used arbitrarily.'),
            '$wa->globals("<em>key</em>", "<em>value</em>")' => _ws('Assigns global var a new value'),
            '$wa->get("<em>key</em>")' => _ws('Returns GET parameter value (same as PHP $_GET["<em>key</em>"])'),
            '$wa->isMobile()' => _ws('Based on current session data returns <em>true</em> or <em>false</em> if user is using a multi-touch mobile device; if no session var reflecting current website version (mobile or desktop) is available, User Agent information is used'),
            '$wa->locale()' => _ws('Returns user locale, e.g. "en_US", "ru_RU". In case user is authorized, locale is retrieved from “Contacts” app user record, or detected automatically otherwise'),
            '$wa->post("<em>key</em>")' => _ws('Returns POST parameter value (same as PHP $_POST["<em>key</em>"])'),
            '$wa->server("<em>key</em>")' => _ws('Returns SERVER parameter value (same as PHP $_SERVER["KEY"])'),
            '$wa->session("<em>key</em>")' => _ws('Returns SESSION var value (same as PHP $_SESSION["<em>key</em>"])'),
            '$wa->snippet("<em>id</em>")' => _ws('Embeds HTML snippet by ID'),
            '$wa->user("<em>field</em>")' => _ws('Returns authorized user data from associated record in “Contacts” app. "<em>field</em>" (string) is optional and indicates the field id to be returned. If not  Returns <em>false</em> if user is not authorized'),
            '$wa->userAgent("<em>key</em>")' => _ws('Returns User Agent info by specified “<em>key</em>” parameter:').'<br />'.
                _ws('— <em>"platform"</em>: current visitor device platform name, e.g. <em>windows, mac, linux, ios, android, blackberry</em>;').'<br />'.
                _ws('— <em>"isMobile"</em>: returns <em>true</em> or <em>false</em> if user is using a multi-touch mobile device (iOS, Android and similar), based solely on User Agent string;').'<br />'.
                _ws('— not specified: returns entire User Agent string;').'<br />',
            '$wa-><em>APP_ID</em>->themePath("<em>theme_id</em>")' => _ws('Returns path to theme folder by <em>theme_id</em> and <em>APP_ID</em>'),
        );

        $app_id = waRequest::get('app');
        $file = waRequest::get('file');
        $vars = array();
        if ($app_id && $app = wa()->getAppInfo($app_id)) {
            $path = $this->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
            if (file_exists($path)) {
                $site = include($path);
                if (isset($site['vars'])) {
                    if (isset($site['vars'][$file])) {
                        $vars += $site['vars'][$file];
                    }
                    if (isset($site['vars']['$wa'])) {
                        $vars += $site['vars']['$wa'];
                    }
                    if (isset($site['vars']['all'])) {
                        $vars += $site['vars']['all'];
                    }
                }
            }
            if ($id = waRequest::get('id')) {
                $page = $this->getPageModel()->getById($id);
                if ($page) {
                    $file = $page['name'];
                    $path = $this->getConfig()->getAppsPath('site', 'lib/config/site.php');
                    if (file_exists($path)) {
                        $site = include($path);
                    }
                    if (isset($site['vars']['page.html'])) {
                        $vars += $site['vars']['page.html'];
                    }
                }
            }
        } else {
            $app = null;
        }

        if (wa()->appExists('site')) {
            $model = new waModel();
            $blocks = $model->query('SELECT * FROM site_block ORDER BY sort')->fetchAll('id');

            $active_app = wa()->getApp();
            $apps = wa()->getApps();
            foreach ($apps as $_app_id => $_app) {
                $path = $this->getConfig()->getAppsPath($_app_id, 'lib/config/site.php');
                if (file_exists($path)) {
                    waLocale::load(wa()->getLocale(), $this->getConfig()->getAppsPath($_app_id, 'locale'), $_app_id, true);
                    $site_config = include($path);
                    if (!empty($site_config['blocks'])) {
                        foreach ($site_config['blocks'] as $block_id => $block) {
                            if (!is_array($block)) {
                                $block = array('content' => $block, 'description' => '');
                            }
                            $block_id = $_app_id.'.'.$block_id;
                            if (!isset($blocks[$block_id])) {
                                $block['id'] = $block_id;
                                $block['app'] = $_app;
                                $blocks[$block_id] = $block;
                            }
                        }
                    }
                }
            }
            wa()->setActive($active_app);
        } else {
            $blocks = array();
        }

        $this->display(array(
            'vars' => $vars,
            'file' => $file,
            'app' => $app,
            'wa_vars' => $wa_vars,
            'smarty_vars' => array(
                '{$foo}' => _ws('Displays a simple variable (non array/object)'),
                '{$foo[4]}' => _ws('Displays the 5th element of a zero-indexed array'),
                '{$foo.bar}' => _ws('Displays the "<em>bar</em>" key value of an array. Similar to PHP $foo["bar"]'),
                '{$foo.$bar}' => _ws('Displays variable key value of an array. Similar to PHP $foo[$bar]'),
                '{$foo->bar}' => _ws('Displays the object property named <em>bar</em>'),
                '{$foo->bar()}' => _ws('Displays the return value of object method named <em>bar()</em>'),
                '{$foo|print_r}' => _ws('Displays structured information about variable. Arrays and objects are explored recursively with values indented to show structure. Similar to PHP var_dump($foo)'),
                '{$foo|escape}' => _ws('Escapes a variable for safe display in HTML'),
                '{$foo|wa_datetime:$format}' => _ws('Outputs <em>$var</em> datetime in a user-friendly form. Supported <em>$format</em> values: <em>monthdate, date, dtime, datetime, fulldatetime, time, fulltime, humandate, humandatetime</em>'),
                '{$x+$y}' => _ws('Outputs the sum of <em>$x</em> and <em>$y</em>'),
                '{$foo=3*4}' => _ws('Assigns variable a value'),
                '{time()}' => _ws('Direct PHP function access. E.g. <em>{time()}</em> displays the current timestamp'),
                '{literal}...{/literal}' => _ws('Content between {literal} tags will not be parsed by Smarty'),
                '{include file="..."}' => _ws('Embeds a Smarty template into the current content. <em>file</em> attribute specifies a template filename within the current design theme folder'),
                '{if}...{else}...{/if}' => _ws('Similar to PHP if statements'),
                '{foreach from=$a key=k item=v}...{foreachelse}...{/foreach}' => _ws('{foreach} is for looping over arrays of data'),
            ),
            'blocks' => $blocks
        ), $this->getConfig()->getRootPath().'/wa-system/page/templates/Help.html');

    }

    /**
     * @return waPageModel
     */
    protected function getPageModel()
    {
        if (!$this->model) {
            $this->model = $this->getAppId().'PageModel';
        }
        return new $this->model();
    }

    protected function getView()
    {
        return wa('webasyst')->getView();
    }

}
