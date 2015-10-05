<?php

class blogPostsWidget extends waWidget
{
    protected $widget;

    public function defaultAction()
    {
        // When viewed from a public dashboard, pretend we're logged in
        $old_user = $user = $this->getUser();
        if (wa()->getUser()->getId() != $user->getId()) {
            $old_user = wa()->getUser();
            wa()->setUser($user);
        }

        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable(wa()->getUser());

        $blog_id = $this->getSettings('blog_id');
        if ($blog_id && !empty($blogs[$blog_id])) {
            $blog_ids = array($blog_id);
        } else {
            $blog_ids = array_keys($blogs);
        }

        $post_model = new blogPostModel();
        $posts = $post_model->search(array(
            'blog_id' => $blog_ids,
        ), array(
            'status' => 'view',
            'author_link' => false,
            'rights' => true,
            'text' => 'cut'
        ), array(
            'blog' => $blogs,
        ))->fetchSearchPage(1, 1);
        wa()->setUser($old_user);

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

    public function getUser()
    {
        if (!wa()->getUser()->getId()) {
            try {
                $c = new waUser($this->info['contact_id']);
                $c->getName();
                return $c;
            } catch (waException $e) {
            }
        }
        return wa()->getUser();
    }
}
