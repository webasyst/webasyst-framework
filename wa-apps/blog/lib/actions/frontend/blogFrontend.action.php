<?php

class blogFrontendAction extends blogViewAction
{
    private $page = 1;
    private $search_params;

    /**
     * @var waSerializeCache
     */
    private $cache = null;

    private $is_lazyloading = false;

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->page = max(1, $this->getRequest()->get('page', 1, waRequest::TYPE_INT));

        $this->search_params = $this->getRequest()->param();

        $layout = $this->getRequest()->get('layout', 'default', waRequest::TYPE_STRING_TRIM);
        if (($layout == 'lazyloading') && ($this->page > 1)) {
            $this->is_lazyloading = true;
        }

        if (false && $this->cache_time = $this->getConfig()->getOption('cache_time')) {
            $params = $this->search_params;
            $params['page'] = $this->page;
            $this->cache_id = blogHelper::buildCacheKey($params);

            /**
             * @todo enable partial caching wait for Smarty 3.2.x
             * @link http://www.smarty.net/forums/viewtopic.php?p=75251
             */
            $this->cache = new waSerializeCache($this->cache_id, $this->cache_time);
            if ($this->cache->isCached()) {
                if ($post_ids = $this->cache->get()) {
                    //get comments count per post
                    $posts = array_fill_keys($post_ids, array());
                    blogHelper::extendPostComments($posts);
                    $this->view->assign('posts', $posts);
                }
            }
        }

        if (!$this->is_lazyloading) {
            $this->setLayout(new blogFrontendLayout());
        }
        $this->setThemeTemplate('stream.html');

        return $this;
    }

    public function execute()
    {
        if ($this->getRequest()->param('blog_id') === false) {
            throw new waException(_w('Blog not found'), 404);
        }
        $this->view->getHelper()->globals($this->getRequest()->param());
        $posts_per_page = max(1, intval($this->getConfig()->getOption('posts_per_page')));

        $post_model = new blogPostModel();
        $options = array();
        if (!$this->appSettings('show_comments', true)) {
            $options['comments'] = false;
        }
        $options['params'] = true;
        $options['text'] = 'cut';
        $options['escape'] = true;

        $is_search = false;

        if (isset($this->search_params["search"])) {
            $plugin = $this->search_params["search"];
            if (!isset($this->search_params["plugin"])) {
                $this->search_params["plugin"] = array();
            }
            if (isset($this->search_params[$plugin])) {
                $this->search_params["plugin"][$plugin] = $this->search_params[$plugin];
                $is_search = true;
            }
        }
        $blogs = blogHelper::getAvailable();


        $posts = $post_model
                 ->search($this->search_params, $options, array('blog' => $blogs))
                 ->fetchSearchPage($this->page, $posts_per_page);

        $stream_title = false;

        if (isset($this->search_params['contact_id'])) {
            if (count($posts)) {
                reset($posts);
                $post = current($posts);
                $name = $post['user']['name'];
                $is_search = true;
            } else {
                if ($contact = blogHelper::getContactInfo($this->search_params['contact_id'])) {
                    $name = htmlentities($contact['name'], ENT_QUOTES, 'utf-8');
                    $is_search = true;
                } else {
                    throw new waException(_w('Blog not found'), 404);
                }
            }
            $stream_title = sprintf(_w('Posts by %s'), $name);
            $this->getResponse()->setTitle($stream_title);
        } elseif ($is_search) {
            $stream_title = $this->getResponse()->getTitle();
        } elseif (isset($this->search_params['year'])) {
            $stream_title = '';
            if (isset($this->search_params['day'])) {
                $stream_title .= intval($this->search_params['day']).' ';
            }
            if (isset($this->search_params['month'])) {
                $stream_title .= _ws(date("F", gmmktime(0, 0, 0, intval($this->search_params['month'])))).' ';
            }

            $stream_title .= $this->search_params['year'].' — '.$this->getResponse()->getTitle();
            $this->getResponse()->setTitle($stream_title);

        }
        $this->view->assign('stream_title', $stream_title);

        $pages = $post_model->pageCount();

        $url = wa()->getRouteUrl('blog/frontend', $this->search_params, true);
        if ($pages && ($pages < $this->page)) {
            $page = min($pages, $this->page);
            $redirect = $url.(($page > 1) ? "?page={$page}" : '');
            $this->getResponse()->redirect($redirect, 302);
        }
        if ($layout = $this->getLayout()) {
            $links = array();
            if ($pages > $this->page) {
                $page = $this->page + 1;
                $links['next'] = "{$url}?page={$page}";
            }
            if ($this->page > 1) {
                $page = $this->page - 1;
                $links['prev'] = $url.(($page > 1) ? "?page={$page}" : '');
            }

            $layout->assign('links', $links);
            if (!$is_search) {
                /*
                 * @deprecated fix assigning sidebar_timeline for next version of blog
                 * */
                $layout->assign('sidebar_timeline', $post_model->getTimeline($this->search_params['blog_id'], $blogs, $this->search_params));
            }

            if (isset($this->search_params['contact_id'])) {
                $layout->assign('action_info', array('search' => array('contact_id' => $this->search_params['contact_id'])));
            }
            $layout->assign('is_search', $is_search);
        }

        $this->view->assign('is_search', $is_search);
        $this->view->assign('page', $this->page);
        $this->view->assign('is_lazyloading', $this->is_lazyloading);
        $this->view->assign('pages', $pages);
        $this->view->assign('post_count', $post_model->searchCount());
        $this->view->assign('show_comments', !isset($options['comments']) || $options['comments']);
        $this->view->assign('posts_per_page', $posts_per_page);

        /**
         * Backward compatibility with older themes
         * @deprecated
         */
        $this->view->assign('is_concrete_blog', waRequest::param('blog_url') ? true : false);
        $this->view->assign('layout_type', $this->is_lazyloading ? 'lazyloading' : (($this->page > 1) ? 'page' : 'default'));


        if ($this->getConfig()->getOption('can_use_smarty')) {
            foreach ($posts as &$post) {
                try {
                    $post['text'] = $this->view->fetch("string:{$post['text']}", $this->cache_id);
                } catch (SmartyException $ex) {
                    $post['text'] = blogPost::handleTemplateException($ex, $post);
                }
            }
            unset($post);
        }
        $this->view->assign('posts', $posts);

        if ($this->cache_time && false) {
            $this->cache->set(array_keys($posts));
        }
    }
}