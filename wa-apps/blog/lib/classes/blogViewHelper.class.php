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
        return wa()->getRouteUrl('blog/frontend/rss', array(), true, wa()->getRouting()->getDomain());
    }

    public function blogs()
    {
        if (!isset($this->avaialable_blogs)) {
            $default_blog_id = intval(wa()->getRouting()->getRouteParam('blog_url_type'));
            if ($default_blog_id < 1) {
                $default_blog_id = null;
            }
            $this->avaialable_blogs = blogHelper::getAvailable(true, $default_blog_id);
            foreach ($this->avaialable_blogs as &$item) {
                $item['name'] = htmlspecialchars($item['name'], ENT_QUOTES, 'utf-8');
                if (!is_array($item['link'])) {
                    $item['link'] = htmlspecialchars($item['link'], ENT_QUOTES, 'utf-8');
                } else {
                    foreach ($item['link'] as &$l) {
                        $l = htmlspecialchars($l, ENT_QUOTES, 'utf-8');
                    }
                }
                $item['title'] = htmlspecialchars($item['title'], ENT_QUOTES, 'utf-8');
            }
        }

        return $this->avaialable_blogs;
    }

    public function blog($blog_id)
    {
        $avaialable_blogs = $this->blogs();
        return isset($avaialable_blogs[$blog_id]) ? $avaialable_blogs[$blog_id] : null;
    }

    /**
     * Get single post entry
     * $extend_options
     * - params: add post params
     *
     * @param int $post_id
     * @param array $extend_options
     * @param array $fields
     *
     * @return mixed
     */
    public function post($post_id, $extend_options = array(), $fields = array())
    {
        $post = null;
        if ($available_blogs = $this->blogs()) {
            $post_model = new blogPostModel();
            $search_options = array('id' => $post_id, 'blog_id' => array_keys($available_blogs));
            $extend_data = array('blog' => $available_blogs);
            $post = $post_model->search($search_options, $extend_options + array('params' => true), $extend_data)->fetchSearchItem($fields);
        }
        $non_escape_fields = array(
            'text'            => true,
            'plugins'         => true,
            'text_before_cut' => true,
        );
        self::escape($post, $non_escape_fields);
        return $post;
    }

    /**
     *
     * Get posts
     *
     * @param int   $blog_id null if all available
     * @param int   $number_of_posts
     * @param array $fields
     *
     * @return array
     */
    public function posts($blog_id = null, $number_of_posts = 20, $fields = array())
    {
        $posts = null;
        if ($available_blogs = $this->blogs()) {
            $post_model = new blogPostModel();

            $search_options = array();
            if ($blog_id === null) {
                $search_options['blog_id'] = array_keys($available_blogs);
            } elseif (isset($available_blogs[$blog_id])) {
                $search_options['blog_id'] = $blog_id;
            } else {
                $available_blogs = blogHelper::getAvailable(false);
                if (in_array($blog_id, $available_blogs)) {
                    $search_options['blog_id'] = $blog_id;
                }
            }
            if ($search_options) {
                $extend_data = array('blog' => $available_blogs);
                $number_of_posts = max(1, $number_of_posts);
                $posts = $post_model->search($search_options, array('params' => true), $extend_data)->fetchSearchPage(1, $number_of_posts, $fields);
            }
        }
        $non_escape_fields = array(
            'text'            => true,
            'text_before_cut' => true,
            'plugins'         => true,
        );
        self::escape($posts, array('*' => $non_escape_fields));
        return $posts;
    }

    public function comments($blog_id = null, $limit = 10)
    {
        $contact_photo_size = 20;

        $limit = max(1, intval($limit));

        $blogs = blogHelper::getAvailable(true, $blog_id);

        $comment_model = new blogCommentModel();

        $fields = array("photo_url_{$contact_photo_size}");
        $blog_ids = array_keys($blogs);

        $comments = $comment_model->getList(array(
            'offset'  => 0,
            'limit'   => $limit,
            'blog_id' => $blog_ids,
            'approved' => true,
        ), $fields);

        $post_ids = array();
        foreach ($comments as $comment) {
            $post_ids[$comment['post_id']] = true;
        }

        //get related posts info
        $post_model = new blogPostModel();
        $search_options = array('id' => array_keys($post_ids));
        $extend_options = array('user' => false, 'link' => true, 'rights' => true, 'plugin' => false, 'comments' => false);
        $extend_data = array('blog' => $blogs);
        $posts = $post_model->search($search_options, $extend_options, $extend_data)->fetchSearchAll(false);
        $comments = blogCommentModel::extendRights($comments, $posts);
        self::escape($comments, array('*' => array('posts' => array('text' => true), 'plugins' => true)));
        return $comments;
    }

    public function postForm($id = null)
    {
        $html = false;
        if (blogHelper::checkRights() >= blogRightConfig::RIGHT_READ_WRITE) {
            $url = wa()->getAppUrl('blog').'?module=post&action=edit';
            $submit = _wd('blog', 'New post');

            $html = <<<HTML

        <form action="{$url}" method="POST" id="{$id}">
        <p>
            <input type="text" name="title"/><br/>
            <textarea name="text" cols="60" rows="20"></textarea><br/>
            {$this->wa()->getView()->getHelper()->csrf()}
            <input type="submit" value="{$submit}"/>
        </p>
        </form>
HTML;
        }
        return $html;
    }

    public function rights($blog_id = true)
    {
        if ($blog_id === true) {
            $name = blogRightConfig::RIGHT_ADD_BLOG;
        } elseif ($blog_id) {
            $name = "blog.{$blog_id}";
        } else {
            $name = "blog.%";
        }
        $user = wa()->getUser();
        $rights = (array)($user->isAdmin('blog') ? blogRightConfig::RIGHT_FULL : $user->getRights('blog', $name));
        $rights[] = blogRightConfig::RIGHT_NONE;
        return max($rights);

    }

    public function timeline($blog_ids = array(), $datetime = array())
    {
        $blogs = blogHelper::getAvailable();
        if (empty($blog_ids)) {
            $blog_ids = array_keys($blogs);
        }
        $blog_post_model = new blogPostModel();
        return $blog_post_model->getTimeline($blog_ids, $blogs, $datetime);
    }

    public function isAdmin()
    {
        return wa()->getUser()->isAdmin('blog');
    }

    public function dataUrl($path = null)
    {
        return wa()->getDataUrl($path, true);
    }

    public function option($name)
    {
        return wa('blog')->getConfig()->getOption($name);
    }

    private static function escape(&$data, $pass = array())
    {
        if (is_array($data)) {
            foreach ($data as $key => &$item) {
                if (isset($pass[$key])) {
                    $pass_item = $pass[$key];

                } else if (isset($pass['*'])) {
                    $pass_item = $pass['*'];
                } else {
                    $pass_item = array();
                }
                if ($pass_item !== true) {
                    self::escape($item, $pass_item);
                }
            }
            unset($item);
        } else {
            $data = htmlspecialchars($data, ENT_QUOTES, 'utf-8');
        }
    }
}
