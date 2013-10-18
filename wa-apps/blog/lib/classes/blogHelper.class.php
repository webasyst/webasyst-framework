<?php
class blogHelper
{
    /**
     *
     * Get blog authors list
     * @param int $blog_id
     * @return array
     */
    public static function getAuthors($blog_id = 0)
    {
        static $cache = array();
        if (!isset($cache[$blog_id])) {
            $rights_model = new waContactRightsModel();
            if ($blog_id) {
                $contact_ids = $rights_model->getUsers('blog', "blog.{$blog_id}", blogRightConfig::RIGHT_READ_WRITE);
            } else {
                $contact_ids = $rights_model->getUsers('blog', "blog.%", blogRightConfig::RIGHT_READ_WRITE);
            }

            $contact_model = new waContactModel();
            $cache[$blog_id] = $contact_model->getName($contact_ids);
        }
        return $cache[$blog_id];
    }

    /**
     * Check blog rights for current or specified user
     * @param int $blog_id null to check blog create
     * @param bool|int $contact_id
     * @param int $mode
     * @throws waRightsException
     * @internal param $blogRightConfig .RIGHT_READ_WRITE|int $mode
     * @return int|null
     */
    public static function checkRights($blog_id = null, $contact_id = true, $mode = blogRightConfig::RIGHT_READ_WRITE)
    {
        static $rights_model;
        $rights = null;
        if (!$rights_model) {
            $rights_model = new waContactRightsModel();
        }

        if ($contact_id === true) {
            $user = wa()->getUser();
            if ($user->isAdmin('blog')) {
                return blogRightConfig::RIGHT_FULL;
            }
            $contact_id = $user->getId();
        } elseif ($contact_id) {
            if ($rights_model->get($contact_id, 'blog', 'backend') > 2) {
                return blogRightConfig::RIGHT_FULL;
            }
        }

        if ($contact_id) { //it's backend

            if ($blog_id) {
                if ($blog_id === true) {
                    $rights = $rights_model->get($contact_id, 'blog', blogRightConfig::RIGHT_ADD_BLOG);
                    if (!$rights) {
                        throw new waRightsException(_w('Access denied'), 403);
                    }
                } else {
                    $rights = $rights_model->get($contact_id, 'blog', "blog.{$blog_id}");
                    if ($rights < $mode) {
                        throw new waRightsException(_w('Access denied'), 403);
                    }
                }
            } else {
                $rights = max($rights_model->get($contact_id, 'blog'));
            }
        } else { //it's frontend
            if ($mode > blogRightConfig::RIGHT_READ) {
                throw new waRightsException(_w('Access denied'), 403);
            }

            $blog_model = new blogBlogModel();

            if (!$blog_id || !in_array($blog_id, array($blog_model->getAvailable(false, array(), $blog_id)))) {
                throw new waRightsException(_w('Access denied'), 403);
            }
            return blogRightConfig::RIGHT_READ;
        }
        return $rights;

    }

    /**
     *
     * Get contact extra info
     * @param int $id
     * @param int|int[] $size
     * @return array|bool
     */
    public static function getContactInfo($id, $size = 50)
    {
        $ids = is_array($id) ? $id : array($id);
        static $cache = array();
        $cached = array_keys($cache);
        if ($search = array_unique(array_diff($ids, $cached))) {
            $user_model = new waContactModel();
            $cache += $user_model->getByField('id', $search, 'id');
        }
        if (is_array($id)) {
            $result = array();
            foreach ($ids as $id) {
                $result[$id] = isset($cache[$id]) ? $cache[$id] : false;
            }
            return $result;
        } elseif (isset($cache[$id])) {
            if (!isset($cache[$id]['photo_url'])) {
                $waContact = new waContact($id);
                $max_size = 0;
                foreach ((array)$size as $s) {
                    $cache[$id]['photo_url_'.$s] = $waContact->getPhoto($s);
                    if ($max_size < $s) {
                        $max_size = $s;
                        $cache[$id]['photo_url'] = $cache[$id]['photo_url_'.$s];
                    }
                }
                unset($waContact);
            }
            return $cache[$id];
        } else {
            return false;
        }
    }

    /**
     *
     * Extend items by contact info
     * @param array $rows
     * @param array $fields
     * @param bool $get_link
     */
    public static function extendUser(&$rows, $fields = array(), $get_link = false)
    {
        $default_fields = array('id', 'name',);
        $fields = array_unique(array_merge($fields, $default_fields));
        $ids = array();
        foreach ($rows as $row) {
            if ($row['contact_id']) {
                $ids[] = intval($row['contact_id']);
            }
        }
        $ids = array_unique($ids);

        $collection = new waContactsCollection($ids);
        $contacts = $collection->getContacts(implode(',', $fields), 0, count($ids));
        $contact = new waContact(0);
        $contacts[0] = array('name' => '');
        $photo_fields = array();
        foreach ($fields as $field) {
            if (preg_match('@^photo_url_(\d+)$@', $field, $matches)) {
                $photo_fields[] = $field;
                $contacts[0][$field] = $contact->getPhoto($matches[1], $matches[1]);
            } else {
                $contacts[0][$field] = $contact->get($field);
            }
        }

        $app_static_url = wa()->getAppStaticUrl();

        foreach ($rows as &$row) {
            $row['user'] = array();
            $id = $row['contact_id'] = max(0, intval($row['contact_id']));
            if (!isset($contacts[$id])) {
                $id = 0;
            }
            if (isset($contacts[$id])) {
                if (isset($row['url']) && $get_link && !isset($contacts[$id]['posts_link'])) {
                    $contacts[$id]['posts_link'] = blogPost::getUrl($row, 'author');
                }
                $row['user'] = $contacts[$id];
            }
            if (!$id || !isset($contacts[$id])) {
                if (isset($row['name'])) {
                    $row['user']['name'] = $row['name'];
                } elseif (isset($row['contact_name'])) {
                    $row['user']['name'] = $row['contact_name'];
                }
                if (isset($row['auth_provider'])) {
                    if ($row['auth_provider'] && ($row['auth_provider'] != blogCommentModel::AUTH_GUEST)) {
                        $row['user']['photo_url'] = "{$app_static_url}img/{$row['auth_provider']}.png";
                        foreach ($photo_fields as $field) {
                            $row['user'][$field] = & $row['user']['photo_url'];
                        }
                    }
                }
            }
            unset($row);
        }
    }

    /**
     *
     * Extend item rights
     * @param $items
     * @param $blogs
     * @param $contact_id
     */
    public static function extendRights(&$items, $blogs = array(), $contact_id = null)
    {
        foreach ($items as &$item) {
            if (isset($item['blog_id']) && isset($blogs[$item['blog_id']])) {
                $item['rights'] = $blogs[$item['blog_id']]['rights'];
            } elseif (!isset($item['rights'])) {
                $item['rights'] = blogRightConfig::RIGHT_NONE;
            }

            $item['editable'] = false;

            if ($item['rights'] >= blogRightConfig::RIGHT_FULL) {
                $item['editable'] = true;
            } elseif ($contact_id && ($item['rights'] >= blogRightConfig::RIGHT_READ_WRITE)) {
                $item_contact_id = null;
                if (isset($item['post']) && isset($item['post']['contact_id'])) {
                    $item_contact_id = $item['post']['contact_id'];
                } elseif (isset($item['contact_id'])) {
                    $item_contact_id = $item['contact_id'];
                }
                if ($item_contact_id && ($contact_id == $item_contact_id)) {
                    $item['editable'] = true;
                }
            }
            unset($item);
        }
    }

    public static function extendPostState(&$posts, $mode = false)
    {
        $user = wa()->getUser();
        $timezone = $user->getTimezone();
        $contact_id = $user->getId();
        $current_datetime = waDateTime::date("Y-m-d", null, $timezone);
        $activity_datetime = blogActivity::getUserActivity();
        $blog_activity = null;
        if ('view' === $mode) {
            $blog_activity = blogActivity::getInstance();
            $viewed_ids = array();
        }
        foreach ($posts as &$post) {
            if ($post['datetime']) {
                if (in_array($post['status'], array(blogPostModel::STATUS_DEADLINE /*,blogPostModel::STATUS_SCHEDULED*/))) {
                    $datetime = waDateTime::date("Y-m-d", $post['datetime'], $timezone);
                    if ($datetime <= $current_datetime) {
                        $post['overdue'] = true;
                    }

                } elseif (in_array($post['status'], array(blogPostModel::STATUS_PUBLISHED))) {
                    if ($activity_datetime && ($post['datetime'] > $activity_datetime) && (!$contact_id || ($contact_id != $post['contact_id']))) {
                        if ('view' === $mode) {
                            $post['new'] = $blog_activity->isNew("b.{$post['blog_id']}", $post['id']);
                            if ($post['new'] == blogActivity::STATE_NEW) {
                                if (!isset($viewed_ids[$post['blog_id']])) {
                                    $viewed_ids[$post['blog_id']] = array();
                                }
                                $viewed_ids[$post['blog_id']][] = $post['id'];
                            } elseif (!$post['new']) {
                                unset($post['new']);
                            }
                        } else {
                            $post['new'] = true;
                        }
                    }
                }
            }
            unset($post);
        }
        if ($blog_activity && !empty($viewed_ids)) {
            foreach ($viewed_ids as $blog_id => $post_ids) {
                $blog_activity->set("b.{$blog_id}", $post_ids);
            }
        }
    }

    /**
     *
     * Get comments for posts
     * @param array $posts
     */
    public static function extendPostComments(&$posts)
    {
        $comment_model = new blogCommentModel();
        $post_ids = array_keys($posts);
        $comment_count = $comment_model->getCount(null, $post_ids);
        $comment_new_count = $comment_model->getCount(null, $post_ids, blogActivity::getUserActivity());
        foreach ($posts as $id => &$post) {
            $post['comment_count'] = isset($comment_count[$id]) ? $comment_count[$id] : 0;
            $post['comment_new_count'] = isset($comment_new_count[$id]) ? $comment_new_count[$id] : 0;
            unset($post);
        }
    }

    /**
     * Build properly cache key for blog item
     *
     * @todo enable partial caching wait for Smarty 3.2.x
     * @link http://www.smarty.net/forums/viewtopic.php?p=75251
     * @param array $params
     * @return string
     */
    public static function buildCacheKey($params = array())
    {
        $fields = array('blog_id' => 'all', 'year' => 'YYYY', 'month' => 'MM', 'layout' => 'default', 'page' => 'all');
        $cache_key = 'posts';
        foreach ($fields as $key => $field) {
            if (is_int($key)) {
                $default = false;
            } else {
                $default = $field;
                $field = $key;
            }
            if (isset($params[$field]) && $params[$field]) {
                $cache_key .= '|'.$params[$field];
            } else if ($default) {
                $cache_key .= '|'.$default;
            } else {
                break;
            }
        }
        return $cache_key;
    }

    /**
     *
     * @see blogBlog::getAvailable
     * @param bool $extended
     * @param int $blog_id
     * @return array
     */
    public static function getAvailable($extended = true, $blog_id = null)
    {
        static $blogs_cache = array();
        $extended = intval($extended) ? true : false;
        $backend = (wa()->getEnv() == 'backend') ? true : false;
        if (!isset($blogs_cache[$extended])) {
            $blog_model = new blogBlogModel();

            $blogs = $blog_model->getAvailable($backend, $extended ? 'name,icon,color,id,url,status' : 'name,id,url', null, $extended);

            foreach ($blogs as $id => &$blog) {
                if ($extended) {
                    $blog['class'] = $blog['color'];

                    if (strpos($blog['icon'], '.')) {
                        $blog['style'] = "background-image: url('{$blog['icon']}'); background-repeat: no-repeat;";
                    } else {
                        $blog['class'] .= ($blog['class'] ? ' ' : '').'icon16 '.$blog['icon'];
                    }
                }
                $blog['value'] = $id;
                $blog['title'] = $blog['name'];
                unset($blog);

            }
            $blogs_cache[$extended] = $blogs;
        } else {
            $blogs = $blogs_cache[$extended];
        }
        return $blog_id ? (isset($blogs[$blog_id]) ? array($blog_id => $blogs[$blog_id]) : array()) : $blogs;
    }

    public static function extendIcon(&$item)
    {
        $title = isset($item['title']) ? $item['title'] : (isset($item['name']) ? $item['name'] : '');
        if ($title) {
            $title = ' title="'.htmlentities($title, ENT_QUOTES, 'utf-8').'"';
        }
        if (isset($item['icon'])) {
            if (strpos($item['icon'], '.')) {
                $item['icon_url'] = $item['icon'];
                $item['icon_html'] = '<i class="icon16" style="background-image: url(\''.$item['icon'].'\'); background-repeat: no-repeat;"'.$title.'></i>';
            } else {
                $item['icon_url'] = false;
                $item['icon_html'] = '<i class="icon16 '.$item['icon'].'"'.$title.'></i>';
            }
        }
    }

    public static function transliterate($slug)
    {
        $slug = preg_replace('/\s+/', '-', $slug);

        if ($slug) {
            foreach (waLocale::getAll() as $lang) {
                $slug = waLocale::transliterate($slug, $lang);
            }
        }

        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '', $slug);
        if (!$slug) {
            $slug = date('Ymd');
        }

        return strtolower($slug);
    }

    /**
     *
     * Get urls (url) of item (blog, post) taking in account multiple settlements
     *
     * @param mixed (integer|null) $blog_id
     * @param string $route_rule
     * @param array $params params of routing
     * @param bool $absolute if url will be absolute
     * @return string
     */
    public static function getUrl($blog_id = null, $route_rule = null, $params = array(), $absolute = true)
    {
        $env = wa()->getEnv();
        if (in_array($env, array('cli', 'backend'))) {
            $routing = wa('blog')->getRouting();
            $domain_routes = $routing->getByApp('blog');
            $current_domain = $routing->getDomain();

            $urls = array();
            $current_domain_urls = array();

            foreach ($domain_routes as $domain => $routes) {
                if ($domain == $current_domain) {
                    $p_url = & $current_domain_urls;
                    $current_routes = $routes;
                } else {
                    $p_url = & $urls;
                }
                foreach ($routes as $route) {
                    if (!isset($route['blog_url_type']) || ($route['blog_url_type'] <= 0) ||
                        (($route['blog_url_type'] > 0) && (($blog_id === null) || ($blog_id == $route['blog_url_type'])))
                    ) {
                        #hack to override current route
                        $route['module'] = 'frontend';
                        $routing->setRoute($route, $domain);
                        $url_variant = $routing->getUrl($route_rule, $params, $absolute);
                        if ($url_variant) {
                            $p_url[] = $url_variant;
                            if ($env == 'cli') {
                                break 2;
                            }
                        }
                    }
                }
            }
            unset($p_url);
            $url = array_merge($current_domain_urls, $urls);
            // restore route
            if (isset($current_routes) && is_array($current_routes) && !empty($current_routes)) {
                $routing->setRoute(array_shift($current_routes), $current_domain);
            }
            if ($env == 'cli') {
                $url = array_shift($url);
            }
        } else {
            $url = wa()->getRouteUrl($route_rule, $params, $absolute);
        }
        return $url;
    }

    /**
     *
     * Get application route settings url
     * @param boolean $all
     * @return string|string[]
     */
    public static function getRouteSettingsUrl($all = false)
    {
        $apps = wa()->getApps();
        $links = array();
        if (isset($apps['site'])) {
            $url = wa()->getAppUrl('site');
            $routes = wa()->getRouting()->getByApp(wa()->getApp());
            foreach ($routes as $domain => $domain_routes) {
                if ($domain_routes) {
                    $links[] = $url.'?domain_id='.$domain.'#/routing/route='.key($domain_routes);
                    if (!$all) {
                        break;
                    }
                }
            }
            if (!$links) {
                $links[] = $url;
            }
        }
        return $all ? $links : array_shift($links);
    }
}//EOF