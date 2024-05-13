<?php

class blogCategoryPlugin extends blogPlugin
{
    protected static $current_category;

    public function postUpdate($post)
    {
        $post_id = $post['id'];
        if (isset($post['plugin']) && isset($post['plugin'][$this->id])) {
            if ($post['plugin'][$this->id]) {
                $categories = array_filter(array_keys($post['plugin'][$this->id]));
            } else {
                $categories = array();
            }
            $category_model = new blogCategoryPostModel();
            $category_model->changePost($post['id'], $categories);
        }
    }

    public function postSearch($options)
    {
        $result = null;
        if (!empty($options['plugin'])) {
            if (!empty($options['plugin'][$this->id])) {
                $result = array();
                $result['join'] = array();
                $category_model = new blogCategoryModel();
                if ($category = $category_model->getByField('url',$options['plugin'][$this->id],'id')) {

                    self::$current_category = reset($category);

                    $result['join'] = array();

                    $result['join']['blog_post_category'] = array(
                        'condition'=>'blog_post_category.post_id = blog_post.id',
                    );

                    $result['where'] = array('blog_post_category.category_id IN ('.implode(', ',array_keys($category)).')');
                } else {
                    $category = array();
                    $result['where'] = 'FALSE';
                }
                $title = array();
                if ($category) {
                    foreach ($category as $item) {
                        $title[] = $item['name'];
                    }
                } else {
                    $title[] = _wp('not found');
                }
                wa()->getResponse()->setTitle(implode(', ', $title));
            }
        }
        return $result;
    }

    public function backendSidebar($param)
    {
        $output = array();
        $action = new blogCategoryPluginBackendSidebarAction();
        $output['section'] = $action->display();
        return $output;
    }

    public function backendPostEdit($post)
    {
        $output = array();
        $action = new blogCategoryToolbarPluginBackendAction(array('post_id'=>$post['id']));
        $action->setTemplate('backend/ToolbarBackend.html', true);
        $output['sidebar'] = $action->display(false);
        return $output;
    }

    public function frontendSidebar($params)
    {
        $output = array();
        $category_id = isset($params['category'])?$params['category']:false;

        if($categories = blogCategory::getAll()){
            $output['sidebar'] = '<ul class="menu-v categories">';
            $wa = wa();
            foreach ($categories as $category) {
                blogHelper::extendIcon($category);
                $category['link'] = $wa->getRouteUrl('blog/frontend', array('category'=>urlencode($category['url'])), true);
                $category['name'] = htmlentities($category['name'],ENT_QUOTES,'utf-8');
                $selected = ($category_id && ($category_id == $category['url'])) ? ' class="selected"':'';
                $output['sidebar'] .= <<<HTML
<li{$selected}>
<a href="{$category['link']}" title="{$category['name']}">{$category['name']}</a>
</li>
HTML;
            }
            $output['sidebar'] .= '</ul>';
        }
        return $output;
    }

    public function frontendPreparePosts(&$posts)
    {
        if (!self::$current_category) {
            return;
        }

        $url_type = $this->getSettings('url_type');
        if ($url_type) {

            if (!$posts) {
                return;
            }

            $dummy_post = reset($posts);
            $dummy_post['url'] = trim(self::$current_category['url'], '/') . '/:POST_URL:';
            $link_template = blogPost::getUrl($dummy_post);

            $prev_id = 0;
            $next_map = array();
            foreach ($posts as &$post) {
                $post['original_link'] = $post['link'];
                $post['link'] = str_replace(':POST_URL:', $post['url'], $link_template);
                $post['plugin_category'] = array(
                    'category' => self::$current_category
                );
                if ($prev_id > 0) {
                    $next_map[$prev_id] = $post['id'];
                }
                $prev_id = $post['id'];
            }
            unset($post);

            foreach ($posts as &$post) {
                if (!isset($next_map[$post['id']])) {
                    continue;
                }
                $next_id = $next_map[$post['id']];
                if (!isset($posts[$next_id])) {
                    continue;
                }
                $next_post = $posts[$next_id];
                $post['plugin_category']['next_link'] = $next_post['link'];
            }
            unset($post);
        }
    }

    public function routing($route = array())
    {
        $blog_id = isset($route['blog_url_type']) ? (int) $route['blog_url_type']: 0;

        switch ($blog_id) {
            case -1:
                $route_id = 2;
                break;
            case 0:
                $route_id = 0;
                break;
            default:
                $route_id = 1;
                break;
        }

        $_all_categories = blogCategory::getAll();
        $_url_type = $this->getSettings('url_type');

        $file = $this->path.'/lib/config/routing.php';
        if (file_exists($file)) {
            /**
             * @var array $route Variable available at routing file
             */
            $routing = include($file);
            return $routing[$route_id];
        }

        return array();
    }
}
