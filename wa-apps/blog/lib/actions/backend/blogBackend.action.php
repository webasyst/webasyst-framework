<?php
/**
 * @author Webasyst
 *
 */
class blogBackendAction extends waViewAction
{
    public function execute()
    {
        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable($this->getUser());

        $stream = array(
            'all_posts' => false
        );
        $title_suffix = '';

        $search_options = array();
        // native search
        if ($text = waRequest::get('text', '')) {
            $text = urldecode($text);
            $search_options['text'] = $text;
            $title_suffix = " / $text";
        }
        // plugins' search
        if ($plugin = waRequest::get('search', false)) {

            $search_options["plugin"] = array();
            if (is_array($plugin)) {
                foreach ($plugin as $plugin_id => $plugin_params) {
                    $search_options["plugin"][$plugin_id] = $plugin_params;
                }
            } else {
                $search_options["plugin"][$plugin] = waRequest::get($plugin, true);
            }
        }

        if ($blog_id = max(0, waRequest::get('blog', null, waRequest::TYPE_INT))) {

            if (!isset($blogs[$blog_id])) {
                throw new waException(_w('Blog not found'), 404);
            }

            wa()->getStorage()->write('blog_last_id', $blog_id);
            $blog = &$blogs[$blog_id];
            $stream['title'] = $blog['name'];
            $stream['link'] = $this->getUrl($blog);
            $stream['blog'] = $blog;

            $search_options['blog_id']=$blog_id;
        } else {
            if(empty($search_options["plugin"])) {
                $stream['title'] = _w('All posts');
                $stream['link'] = $this->getUrl();
                $stream['all_posts'] = true;
            } else {
                $stream['title'] = '';
                $stream['link'] = '';
            }
            $stream['blog'] = null;

            $search_options['blog_id']=array_keys($blogs);
        }
        $this->getResponse()->setTitle($stream['title'].$title_suffix);

        $search = false;



        $page = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
        $posts_per_page = max(1, intval($this->getConfig()->getOption('posts_per_page')));

        $extend_options = array();
        $extend_options['status'] = 'view';
        $extend_options['author_link'] = false;
        $extend_options['rights'] = true;
        if (!$this->getRequest()->isMobile()) {
            $extend_options['text'] = 'cut';
        }

        $post_model = new blogPostModel();
        $posts = $post_model
        ->search($search_options, $extend_options, array('blog' => $blogs))
        ->fetchSearchPage($page, $posts_per_page);

        if ($page == 1) {
            $stream['title'] = $this->getResponse()->getTitle();
            $this->chooseLayout();
            $this->view->assign('search', $plugin ? urldecode(http_build_query(array('search'=>$plugin))) : null);


            /**
             * Backend posts stream view page
             * UI hook allow extends backend posts view page
             * @event backend_stream
             * @param array[string]mixed $stream Array of stream properties
             * @param array[string]array $stream['blog'] Related blog data array or null
             * @param array[string]string $stream['title'] Stream title
             * @param array[string]string $stream['link'] Stream link
             * @return array[string][string]string $return['%plugin_id%']['menu'] Stream context menu html
             */
            $this->view->assign('backend_stream', wa()->event('backend_stream', $stream, array('menu')));
        }


        $this->view->assign('blogs', $blogs);
        $this->view->assign('blog_id', $blog_id);
        $this->view->assign('text', $text);
        
        $this->view->assign('stream', $stream);

        $this->view->assign('page', $page);

        $this->view->assign('pages', $post_model->pageCount());
        $this->view->assign('posts_total_count', $post_model->searchCount());
        $this->view->assign('posts_count', ($page - 1) * $posts_per_page + count($posts));
        $this->view->assign('posts_per_page', $posts_per_page);
        $this->view->assign('contact_rights', $this->getUser()->getRights('contacts', 'backend'));
        if ($this->getConfig()->getOption('can_use_smarty')) {
            foreach ($posts as &$post) {
                try {
                    $post['text'] = $this->view->fetch("string:{$post['text']}",$this->cache_id);
                } catch (SmartyException $ex) {
                    $post['text'] = blogPost::handleTemplateException($ex, $post);
                }
            }
            unset($post);
        }
        $this->view->assign('posts', $posts);
    }

    private function getUrl($blog = null)
    {
        $url = false;
        if (!$blog) {
            $urls = blogBlog::getUrl(false, true);
        } else if (isset($blog['status']) && $blog['status'] == blogBlogModel::STATUS_PUBLIC) {
            $urls = blogBlog::getUrl($blog, true);
        }
        if (isset($urls) && is_array($urls)) {
            $url = array_shift($urls);
        }
        return $url;
    }

    private function chooseLayout()
    {
        $this->setLayout(!$this->getRequest()->isMobile() ? new blogDefaultLayout() : new blogMobileLayout());
    }


    protected function getTemplate()
    {
        $template = parent::getTemplate();
        if ($this->getRequest()->isMobile()) {
            $template = str_replace('actions', 'actions-mobile', $template);
        }
        return $template;
    }

}
//EOF