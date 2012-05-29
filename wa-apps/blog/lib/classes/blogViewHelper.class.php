<?php
class blogViewHelper extends waAppViewHelper
{

    private $avaialable_blogs;

    public function url()
    {
        return blogBlog::getUrl();
    }

    /**
     *
     * @deprecated
     */
    public function rss()
    {
        return $this->rssUrl();
    }

    public function rssUrl()
    {
        return $this->wa->getRouteUrl('blog/frontend/rss',array(), true);
    }

    public function blogs()
    {
        if (!isset($this->avaialable_blogs)) {
            $default_blog_id = intval(wa()->getRouting()->getRouteParam('blog_url_type'));
            if ($default_blog_id<1) {
                $default_blog_id = null;
            }
            $this->avaialable_blogs = blogHelper::getAvailable(true,$default_blog_id);
        }

        return $this->avaialable_blogs;
    }

    public function blog($blog_id)
    {
        $avaialable_blogs = $this->blogs();
        return isset($avaialable_blogs[$blog_id])?$avaialable_blogs[$blog_id]:null;
    }

    /**
     * Get single post entry
     * @param int $post_id
     * @param array $fields
     * @return mixed
     */
    public function post($post_id, $fields = array())
    {
        $post = null;
        if ($available_blogs = $this->blogs()) {
            $post_model = new blogPostModel();
            $search_options = array('id'=>$post_id,'blog_id'=>array_keys($available_blogs));
            $extend_data = array('blog'=>$available_blogs);
            $post = $post_model->search($search_options,null,$extend_data)->fetchSearchItem($fields);
        }
        return $post;
    }

    /**
     *
     * Get posts
     * @param int $blog_id
     * @param int $number_of_posts
     * @param array $fields
     */
    public function posts($blog_id = null, $number_of_posts=20, $fields = array())
    {
        $posts = null;
        if ($available_blogs = $this->blogs()) {
            $post_model = new blogPostModel();

            $search_options = array();
            if ($blog_id === null) {
                $search_options['blog_id'] = array_keys($available_blogs);
            } elseif (isset($available_blogs[$blog_id])) {
                $search_options['blog_id'] = $blog_id;
            }
            if ($search_options) {
                $extend_data = array('blog'=>$available_blogs);
                $number_of_posts = max(1,$number_of_posts);
                $posts = $post_model->search($search_options,null,$extend_data)->fetchSearchPage(1,$number_of_posts,$fields);
            }
        }
        return $posts;
    }

    public function rights($blog_id = true)
    {
        if ($blog_id === true) {
            $name = blogRightConfig::RIGHT_ADD_BLOG;
        } elseif($blog_id) {
            $name = "blog.{$blog_id}";
        } else {
            $name = "blog.%";
        }
        $rights = (array)wa()->getUser()->getRights('blog',$name);
        $rights[] = blogRightConfig::RIGHT_NONE;
        return max($rights);

    }

    public function isAdmin()
    {
        return wa()->getUser()->isAdmin('blog');
    }

    public function dataUrl($path = null)
    {
        return wa()->getDataUrl($path, true);
    }

    public function config()
    {
        return wa()->getConfig();
    }

    public function option($name)
    {
        return wa()->getConfig()->getOption($name);
    }
}
