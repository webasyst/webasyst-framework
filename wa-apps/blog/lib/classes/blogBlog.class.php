<?php

class blogBlog
{
    static function getUrl($blog = null, $absolute = false)
    {
        $params = array();

        $blog_id = isset($blog['id']) ? $blog['id'] : null;
        if ($blog && isset($blog['url']) && $blog['url']) {
            $params['blog_url'] = $blog['url'];
        } elseif ($blog) {
            $params['blog_url'] = '%blog_url%';
        }

        return blogHelper::getUrl($blog_id, 'blog/frontend', $params, $absolute);
    }
}