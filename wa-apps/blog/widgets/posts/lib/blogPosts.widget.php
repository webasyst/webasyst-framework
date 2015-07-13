<?php

class blogPostsWidget extends waWidget
{
    protected $widget;

    public function defaultAction()
    {
        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable(wa()->getUser());
        $post_model = new blogPostModel();
        $search_options = array(
            'blog_id' => array_keys($blogs)
        );

        $extend_options = array (
            'status' => 'view',
            'author_link' => false,
            'rights' => true,
            'text' => 'cut'
        );
        $posts = $post_model->search($search_options, $extend_options, array(
            'blog' => $blogs,
        ))->fetchSearchPage(1, 1);
        $this->display(array(
            'posts' => $posts
        ));
    }
}