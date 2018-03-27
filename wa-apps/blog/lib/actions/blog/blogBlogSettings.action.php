<?php
/**
 * @author Webasyst
 *
 */
class blogBlogSettingsAction extends waViewAction
{

    public function execute()
    {

        $this->setLayout(new blogDefaultLayout());

        $blog_id = (int) waRequest::get('blog');
        $blog_model = new blogBlogModel();

        if (($blog_id && ($this->getRights("blog.{$blog_id}")<blogRightConfig::RIGHT_FULL)) || (!$blog_id && !$this->getRights(blogRightConfig::RIGHT_ADD_BLOG))) {
            throw new waRightsException(_w('Access denied'));
        }

        // save settings (POST)
        $settings = waRequest::post('settings');

        $draft_data = array();
        $validate_messages = array();
        if ($settings) {

            $settings['status'] = (isset($settings['status'])) ? blogBlogModel::STATUS_PUBLIC : blogBlogModel::STATUS_PRIVATE;
            $settings['name'] = trim($settings['name']);
            $settings['icon'] = (!empty($settings['icon_url'])) ? $settings['icon_url'] : $settings['icon'];

            if (isset($settings['qty'])) {
                unset($settings['qty']);
            }

            if (isset($settings['sort'])) {
                unset($settings['sort']);
            }

            $settings['id'] = $blog_id;
            $validate_messages = $this->validate($settings);
            if (!$validate_messages) {

                //TODO handle settings
                if ($blog_id) {
                    $new_blog_created = false;
                    $blog_model->updateById($blog_id, $settings);
                    $this->logAction('blog_modify', $blog_id);
                } else {
                    $new_blog_created = true;
                    $settings['sort'] = (int) $blog_model->select('MAX(`sort`)')->fetchField() + 1;
                    $settings['id'] = $blog_id = $blog_model->insert($settings);
                    $this->getUser()->setRight($this->getApp(), "blog.{$blog_id}", blogRightConfig::RIGHT_FULL);
                    $this->logAction('blog_add', $blog_id);
                }

                // Create new settlement if asked to
                if ($settings['status'] != blogBlogModel::STATUS_PRIVATE) {
                    $new_route_setup = waRequest::post('new_route_setup', 0, waRequest::TYPE_INT);
                    $route_enabled = waRequest::post('route_enabled', 0, waRequest::TYPE_INT);
                    if ($new_route_setup && $route_enabled) {
                        $this->saveNewRoute($blog_id);
                    }
                } else {
                    $this->removeRoutes($blog_id);
                }

                // refresh qty post in blogs
                $blog_model->recalculate($blog_id);

                /**
                 * @event blog_save
                 * @param array[string]mixed $data
                 * @param array[string]int $data['id']
                 * @param array[string][string]mixed $data['plugin']['%plugin_id']
                 * @return void
                 */
                wa()->event('blog_save', $data);
                if ($new_blog_created) {
                    $this->redirect(array(
                        'blog' => $blog_id
                    ));
                }
            }

            $draft_data = $settings;
        }

        $colors = $this->getConfig()->getColors();
        $icons = $this->getConfig()->getIcons();

        if ($blog_id) {

            if (!$blog = $blog_model->search(array(
                'blog' => $blog_id
            ), array(
                'link' => false
            ))->fetchSearchItem()) {
                throw new waException(_w('Blog not found'), 404);
            }

            $blog['other_settlements'] = blogBlogModel::getPureSettlements($blog);
            $main_settlement_id = null;
            foreach($blog['other_settlements'] as $s_id => $s) {
                if (empty($blog['settlement']) || !$s['single']) {
                    $main_settlement_id = $s_id;
                    $blog['settlement'] = $s;
                    if (!$s['single']) {
                        break;
                    }
                }
            }
            unset($blog['other_settlements'][$main_settlement_id]);
            $blog['other_settlements'] = array_values($blog['other_settlements']);

        } else {

            $blog = array(
                'id'     => false,
                'name'   => '',
                'status' => blogBlogModel::STATUS_PUBLIC,
                'icon'   => current($icons),
                'color'  => current($colors),
                'url'    => false,

            );

            $blogs = array($blog);
            $blogs = $blog_model->prepareView($blogs, array(
                'link' => false
            ));
            $blog = array_shift($blogs);
            $blog['other_settlements'] = blogBlogModel::getPureSettlements($blog);
            $blog['settlement'] = array_shift($blog['other_settlements']);
        }

        $this->getResponse()->setTitle($blog_id ? trim(sprintf(_w('%s settings'), $blog['name'])) : _w('New blog'));

        $blog = !$draft_data ? $blog : array_merge($blog, $draft_data);
        $posts_total_count = 0;
        if ($blog_id) {
            $post_model = new blogPostModel();
            $posts_total_count = $post_model->countByField('blog_id', $blog_id);
        }

        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable($this->getUser());
        foreach($blogs as $id => $b) {
            if ($b['rights'] < blogRightConfig::RIGHT_FULL) {
                unset($blogs[$id]);
            }
        }

        /**
         * Backend blog settings
         * UI hook allow extends backend blog settings page
         * @event backend_blog_edit
         * @param array[string]mixed $blog Blog data
         * @param array['id']int $blog['id'] Blog ID
         * @return array[string][string]string $return['%plugin_id%']['settings'] Blog extra settings html fields
         */
        $this->view->assign('backend_blog_edit', wa()->event('backend_blog_edit', $blog, array('settings')));
        $this->view->assign('posts_total_count', $posts_total_count);

        $this->view->assign('messages', $validate_messages);
        $this->view->assign('saved', waRequest::post() && !$validate_messages);
        $this->view->assign('is_admin', wa()->getUser()->isAdmin('blog'));
        $this->view->assign('domains', wa()->getRouting()->getDomains());
        $this->view->assign('blog_id', $blog_id);
        $this->view->assign('colors', $colors);
        $this->view->assign('icons', $icons);
        $this->view->assign('blogs', $blogs);
        $this->view->assign('blog', $blog);
    }
    public function validate(&$data)
    {
        $messages = array();

        $new_route_setup = waRequest::post('new_route_setup', 0, waRequest::TYPE_INT);
        $route_enabled = waRequest::post('route_enabled', 0, waRequest::TYPE_INT);
        $no_settlement = $new_route_setup && !$route_enabled;

        if ($data['status'] != blogBlogModel::STATUS_PRIVATE && !$no_settlement) {
            if (isset($data['id'])) {
                $url_validator = new blogSlugValidator(array('id' => $data['id']));
            } else {
                $url_validator = new blogSlugValidator();
            }

            $url_validator->setSubject(blogSlugValidator::SUBJECT_BLOG);

            $name_validator = new waStringValidator(array(
                'max_length' => 255,
                'required'   => true
            ), array(
                'required' => _w('Blog name must not be empty')
            ));

            if (!$url_validator->isValid($data['url'])) {
                $messages['blog_url'] = current($url_validator->getErrors());
            }
            if (!$name_validator->isValid($data['name'])) {
                $messages['blog_name'] = current($name_validator->getErrors());
            }
        } else {
            $blog_model = new blogBlogModel();
            if (!$data['id']) {
                $data['url'] = $blog_model->genUniqueUrl($data['name']);
            } else {
                $url = $blog_model->select('url')->where('id = i:id', array('id' => $data['id']))->fetchField('url');
                $data['url'] = $url ? $url : $blog_model->genUniqueUrl($data['name']);
            }
        }

        /**
         * @event blog_validate
         * @param array[string]mixed $data
         * @param array['plugin']['%plugin_id%']mixed plugin data
         * @return array['%plugin_id%']['field']string error
         */
        $messages['plugin'] = wa()->event('blog_validate', $data);
        if (empty($messages['plugin'])) {
            unset($messages['plugin']);
        }

        return $messages;
    }

    /** Remove all frontend routes for selected blog */
    protected function removeRoutes($blog_id)
    {
        $path = $this->getConfig()->getPath('config', 'routing');
        if (!file_exists($path) || !is_writable($path)) {
            return;
        }

        $something_changed = false;
        $route_config = include($path);
        foreach($route_config as $domain => $routes) {
            if (is_array($routes)) {
                foreach($routes as $k => $route) {
                    if (!empty($route['app']) && ($route['app'] == 'blog') && !empty($route['blog_url_type']) && $route['blog_url_type'] == $blog_id) {
                        unset($route_config[$domain][$k]);
                        $something_changed = true;
                    }
                }
            }
        }

        if ($something_changed) {
            waUtils::varExportToFile($route_config, $path);
        }
    }

    /** Create new route for this blog if data came via POST */
    protected function saveNewRoute($blog_id)
    {
        // User asked to create new route?
        if (!waRequest::request('route_enabled')) {
            return true;
        }

        // Make sure routing config is writable, and load existing routes
        $path = $this->getConfig()->getPath('config', 'routing');
        if (file_exists($path)) {
            if (!is_writable($path)) {
                return false;
            }
            $routes = include($path);
        } else {
            $routes = array();
        }

        // Route domain
        $domain = waRequest::post('route_domain', '', 'string');
        if (!isset($routes[$domain])) {
            return false;
        }

        // Route URL
        $url = waRequest::post('settings', array(), 'array');
        $url = ifempty($url['url'], '');
        $url = rtrim($url, '/*');
        $url .= ($url?'/':'').'*';

        // Determine new numeric route ID
        $route_ids = array_filter(array_keys($routes[$domain]), 'intval');
        $new_route_id = $route_ids ? max($route_ids) + 1 : 1;

        $new_route = array(
            'url' => $url,
            'app' => $this->getAppId(),
            'blog_url_type' => $blog_id,
            'theme' => 'default',
            'theme_mobile' => 'default',
        );

        if ($new_route['url'] == '*') {
            // Add as the last rule
            $routes[$domain][$new_route_id] = $new_route;
        } else {
            // Add as the first rule
            $routes[$domain] = array($new_route_id => $new_route) + $routes[$domain];
        }

        waUtils::varExportToFile($routes, $path);
        return true;
    }
}

