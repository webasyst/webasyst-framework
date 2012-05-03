<?php
/**
 * @author WebAsyst Team
 *
 */
class blogBackendAction extends waViewAction
{
    public function execute()
    {
        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable($this->getUser());

        $stream = array();
        $search_options = array();

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
            $stream['title'] = _w('All posts');
            $stream['link'] = $this->getUrl();
            $stream['blog'] = null;

            $search_options['blog_id']=array_keys($blogs);
        }

        $this->getResponse()->setTitle($stream['title']);

        $search = false;

        if ($plugin = waRequest::get('search', false)) {
            if (!isset($search_options["plugin"])) {
                $search_options["plugin"] = array();
            }
            $search_options["plugin"][$plugin] = waRequest::get($plugin, true);
        }

        $page = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
        $posts_per_page = max(1, intval($this->getConfig()->getOption('posts_per_page')));

        $extend_options = array();
        $extend_options['status'] = true;
        $extend_options['author_link'] = false;
        $extend_options['rights'] = true;
        $extend_options['text'] = 'cut';

        $post_model = new blogPostModel();
        $posts = $post_model
                    ->search($search_options, $extend_options, array('blog' => $blogs))
                    ->fetchSearchPage($page, $posts_per_page);

        if ($page == 1) {
            $stream['title'] = $this->getResponse()->getTitle();
            $this->setLayout(new blogDefaultLayout());
            $this->view->assign('plugin', $plugin);
        }


        $this->view->assign('blogs', $blogs);
        $this->view->assign('blog_id', $blog_id);

        $this->view->assign('stream', $stream);
        $this->view->assign('posts', $posts);

        $this->view->assign('page', $page);

        $this->view->assign('pages', $post_model->pageCount());
        $this->view->assign('posts_total_count', $post_model->searchCount());
        $this->view->assign('posts_count', ($page - 1) * $posts_per_page + count($posts));
        $this->view->assign('posts_per_page', $posts_per_page);
        $this->view->assign('contact_rights', $this->getUser()->getRights('contacts', 'backend'));
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

}
//EOF