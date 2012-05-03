<?php

class blogFrontendAction extends blogViewAction
{
    private $blog_id = null;
    private $page = 1;
    private $search_params;

    /**
     * @var waSerializeCache
     */
    private $cache = null;

    private $layout_type = 'default';

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->page = max(1,$this->getRequest()->get('page',1,waRequest::TYPE_INT));

        $this->search_params = $this->getRequest()->param();

        if ($blog_id = $this->getRequest()->param('blog_id')) {
            $this->blog_id = $blog_id;
        }

        $this->layout_type = $this->getRequest()->get('layout','default',waRequest::TYPE_STRING_TRIM);
        if (($this->layout_type == 'default') && ($this->page > 1)) {
            $this->layout_type = 'page';
        }

        if (false && $this->cache_time = $this->getConfig()->getOption('cache_time') ) {
            $params = $this->getRequest()->param();
            $params['layout'] = $layout;
            $params['page'] = $this->getRequest()->get('page',1,waRequest::TYPE_INT);
            $this->cache_id = blogHelper::buildCacheKey($params);

            /**
             * @todo enable partial caching wait for Smarty 3.2.x
             * @link http://www.smarty.net/forums/viewtopic.php?p=75251
             */
            //$this->cache = new waSerializeCache($this->cache_id, $this->cache_time);
            if (false && $this->cache->isCached()) {
                if ($post_ids = $this->cache->get()) {
                    //get comments count per post
                    $posts = array_fill_keys($post_ids, array());
                    blogHelper::extendPostComments($posts);
                    $this->view->assign('posts',$posts);
                }
            }
        }

        $this->setThemeTemplate('stream.html');

        switch ($this->layout_type) {
            case 'lazyloading': {
                break;
            }
            default: {
                $this->setLayout(new blogFrontendLayout());
                break;
            }
        }

        return $this;
    }

    public function execute()
    {
        $this->view->getHelper()->globals($this->getRequest()->param());
        $posts_per_page = max(1,intval($this->getConfig()->getOption('posts_per_page')));

        $post_model = new blogPostModel();
        $options = array();
        if (!$this->appSettings('show_comments', true)) {
            $options['comments'] = false;
        }
        $options['params'] = true;
        $options['text'] = 'cut';

        $annotation_only = false;

        if (isset($this->search_params["search"])) {
            $plugin = $this->search_params["search"];
            if (!isset($this->search_params["plugin"])) {
                $this->search_params["plugin"] = array();
            }
            if( isset($this->search_params[$plugin])) {
                $this->search_params["plugin"][$plugin] = $this->search_params[$plugin];
                $annotation_only = true;
            }
        }
        $blogs = blogHelper::getAvailable();

        $posts = $post_model
        ->search($this->search_params, $options,array('blog'=>$blogs))
        ->fetchSearchPage($this->page,$posts_per_page);

        $stream_title = false;

        if (isset($this->search_params['contact_id'])) {
            if (count($posts)) {
                reset($posts);
                $post = current($posts);
                $name = $post['user']['name'];
                $annotation_only = true;
            } else {
                if ($contact = blogHelper::getContactInfo($this->search_params['contact_id'])) {
                    $name = $contact['name'];
                    $annotation_only = true;
                } else {
                    throw new waException(_w('Blog not found'), 404);
                }
            }
            $this->getResponse()->setTitle($name);
            $stream_title =sprintf(_w('Posts by %s'),$name);
        }
        $this->view->assign('stream_title', $stream_title);

        $pages = $post_model->pageCount();

        $url = wa()->getRouteUrl('blog/frontend', $this->search_params, true);
        if ($pages && ($pages<$this->page)) {
            $page = min($pages,$this->page);
            $redirect = $url.(($page>1)?"?page={$page}":'');
            $this->getResponse()->redirect($redirect,302);
        }
        if ($layout = $this->getLayout()) {
            $links = array();
            $links['canonical'] = $url.(($this->page>1)?"?page={$this->page}":'');
            if ($pages > $this->page) {
                $page = $this->page+1;
                $links['next'] = "{$url}?page={$page}";
            }
            if ($this->page>1) {
                $page = $this->page-1;
                $links['prev'] = $url.(($page>1)?"?page={$page}":'');
            }

            $layout->assign('links',$links);
            if (!$stream_title) {
                $layout->assign('sidebar_timeline', $post_model->getTimeline($this->search_params['blog_id'],$blogs));
            }
        }

        //handle search result
        if (isset($this->search_params['contact_id']) && ($layout = $this->getLayout())) {
            $layout->assign('action_info', array('search'=>array('contact_id'=>$this->search_params['contact_id'])));
        }

        $this->view->assign('annotation_only',$annotation_only);
        $this->view->assign('posts', $posts);
        $this->view->assign('page',$this->page);
        $this->view->assign('layout_type',$this->layout_type);
        $this->view->assign('pages', $pages);
        $this->view->assign('post_count',$count = $post_model->searchCount());
        $this->view->assign('show_comments',!isset($options['comments']) || $options['comments']);
        $this->view->assign('post_count_string',_w('%d post','%s posts',$count,true));
        $this->view->assign('posts_per_page',$posts_per_page);
        $this->view->assign('post_params',$this->search_params);
        $this->view->assign('is_concrete_blog', isset($this->search_params['blog_url']));

        if ($this->cache_time && false) {
            $this->cache->set(array_keys($posts));
        }
    }
}