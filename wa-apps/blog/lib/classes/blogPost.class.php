<?php

class blogPost
{
    static function getUrl($post,$type = 'post')
    {
        static $blog_urls = array();

        $params = array();
        $fields = array('blog_url','year','month','day');
        foreach ($fields as $field) {
            if (isset($post[$field])) {
                $params[$field] = $post[$field];
            }
        }
        if (isset($post['id']) && $post['id'] && isset($post['url']) && $post['url']) {
            $params['post_url'] = $post['url'];
        } elseif($type != 'timeline') {
            $params['post_url'] = '%post_url%';
        }

        $blog_id = null;
        if ($type != 'author') {
            if (isset($post['datetime']) && $post['datetime'] && $time = date_parse($post['datetime'])) {
                $params['post_year'] = sprintf('%04d',$time['year']);
                $params['post_month'] = sprintf('%02d',$time['month']);
                $params['post_day'] = sprintf('%02d',$time['day']);
            } elseif ($type != 'timeline') {
                $params['post_year'] = '%year%';
                $params['post_month'] = '%month%';
                $params['post_day'] = '%day%';
            }
            if (!isset($params['blog_url']) && isset($post['blog_id'])) {
                $blog_id = $post['blog_id'];

                if (!isset($blog_urls[$blog_id])) {
                    $blog_urls[$blog_id] = $blog_id;
                    $blog_model = new blogBlogModel();
                    if ($blog_data = $blog_model->getById($blog_id)) {
                        if (strlen($blog_data['url'])) {
                            $blog_urls[$blog_id] = $blog_data['url'];
                        }
                    }
                }
                $params['blog_url'] = $blog_urls[$blog_id];
            } elseif (isset($params['blog_url']) && isset($post['blog_id'])) {
                $blog_id = $post['blog_id'];
            }
        }
        $route = false;

        switch ($type) {
            case 'comment':{
                $route = 'blog/frontend/comment';
                break;
            }
            case 'timeline': {
                $route = 'blog/frontend';
                break;
            }
            case 'author': {
                if ($params['contact_id'] = $post['contact_id']) {
                    $route = 'blog/frontend';
                }
                break;
            }
            case 'post':
            default:{
                $route = 'blog/frontend/post';
                break;
            }
        }
        return $route?blogHelper::getUrl($blog_id, $route, $params):false;
    }

    static function move($blog_id, $move_blog_id)
    {
        if ($blog_id != $move_blog_id) {
            $post_model = new blogPostModel();
            $post_model->updateByField('blog_id', $blog_id, array('blog_id'=>$move_blog_id));

            $comment_model = new blogCommentModel();
            $comment_model->updateByField('blog_id', $blog_id, array('blog_id'=>$move_blog_id));

            $blog_model = new blogBlogModel();
            $blog_model->recalculate(array($blog_id, $move_blog_id));
        }
    }

}