<?php

class blogPostsWidget extends waWidget
{
    protected $widget;

    public function defaultAction()
    {
        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable(wa()->getUser());

        $blog_id = $this->getSettings('blog_id');
        if ($blog_id && !empty($blogs[$blog_id])) {
            $blog_ids = array($blog_id);
        } else {
            $blog_ids = array_keys($blogs);
        }

        $search_options = array(
            'blog_id' => $blog_ids,
        );
        $extend_options = array (
            'status' => 'view',
            'author_link' => false,
            'rights' => true,
            'text' => 'cut'
        );
        $post_model = new blogPostModel();
        $posts = $post_model->search($search_options, $extend_options, array(
            'blog' => $blogs,
        ))->fetchSearchPage(1, 1);
        $post = reset($posts);
        $blog = false;
        if ($post && !empty($blogs[$post['blog_id']])) {
            $blog = $blogs[$post['blog_id']];
        }

        $this->display(array(
            'blog' => $blog,
            'post' => $post,
        ));
    }

    // List of blogs for settings page
    protected function getSettingsConfig()
    {
        $blogs = array();
        $blog_model = new blogBlogModel();
        $blogs[''] = _wp('All blogs');
        foreach($blog_model->getAvailable(wa()->getUser()) as $b) {
            $blogs[$b['id']] = $b['name'];
        }
        $result = parent::getSettingsConfig();
        $result['blog_id']['options'] = $blogs;
        return $result;
    }
}
