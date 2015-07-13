<?php

class blogCommentsWidget extends waWidget
{
    protected $widget;

    public function defaultAction()
    {
        $user = $this->getUser();
        $filter = $this->getSettings('filter', 'all');
        $blogs = blogHelper::getAvailable();
        $blog_ids = array_keys($blogs);

        $comments = $this->getComments(array(
            'blog_id' => $blog_ids,
            'filter' => $filter,
            'offset' => 0,
            'limit' => 6,
        ));

        // get related posts info
        $post_ids = array();
        foreach ($comments as $comment) {
            $post_ids[$comment['post_id']] = true;
        }
        $post_model = new blogPostModel();
        $extend_data = array('blog' => $blogs);
        $search_options = array('id' => array_keys($post_ids));
        $extend_options = array('user' => false, 'link' => true, 'rights' => true, 'plugin' => false, 'comments' => false);
        $posts = $post_model->search($search_options, $extend_options, $extend_data)->fetchSearchAll(false);

        $comments = blogCommentModel::extendRights($comments, $posts);

        $this->display(array(
            'blogs' => $blogs,
            'posts' => $posts,
            'comments' => $comments,
            'filter' => $filter,
        ));
    }

    protected function getComments($search_options)
    {
        if (empty($search_options['post_id'])) {
            $search_options['post_id'] = null;
        }

        if (!isset($search_options['blog_id'])) {
            $search_options['blog_id'] = array_keys(blogHelper::getAvailable());
        } else if (is_numeric($search_options['blog_id'])) {
            $search_options['blog_id'] = array((int)$search_options['blog_id']);
        } else if (!is_array($search_options['blog_id'])) {
            $search_options['blog_id'] = array();
        }

        if (is_numeric($search_options['filter'])) {
            $search_options['filter'] = (int) $search_options['filter'];
            if (in_array($search_options['filter'], $search_options['blog_id'])) {
                $search_options['blog_id'] = array($search_options['filter']);
            } else {
                $search_options['blog_id'] = array();
            }
        } else if ($search_options['filter'] == 'myposts') {
            if (empty($search_options['blog_id'])) {
                $search_options['post_id'] = array();
            } else {
                $post_model = new blogPostModel();
                $search_options['post_id'] = array_keys($post_model->select('id')->where('contact_id=? AND blig_id IN (?)', array(
                    $this->getUser()->getId(),
                    $search_options['blog_id']
                ))->fetchAll('id'));
            }
        }

        $search_options['approved'] = true;

        $comment_model = new blogCommentModel();
        return $comment_model->getList($search_options, array("photo_url_20"), array(
            'datetime' => blogActivity::getUserActivity(),
        ));
    }
}
