<?php

class blogPostSearchMethod extends waAPIMethod
{
    protected $method = 'GET';

    public function execute()
    {
        $hash = $this->get('hash');

        $offset = waRequest::get('offset', 0, 'int');
        if ($offset < 0) {
            throw new waAPIException('invalid_param', 'Param offset must be greater than or equal to zero');
        }
        $limit = waRequest::get('limit', 100, 'int');
        if ($limit < 0) {
            throw new waAPIException('invalid_param', 'Param limit must be greater than or equal to zero');
        }
        if ($limit > 1000) {
            throw new waAPIException('invalid_param', 'Param limit must be less or equal 1000');
        }
        
        $options = array();
        $hash = explode('/', trim($hash, '/'));
        $hash[1] = isset($hash[1]) ? $hash[1] : '';
        switch ($hash[0]) {
            case 'blog': 
                $options['blog_id'] = (int) $hash[1];
                break;
            case 'contact':
            case 'author':
                $options['contact_id'] = (int) $hash[1];
                break;
            case 'search':
                $options['text'] = $hash[1];
                break;
            case 'tag':
                // use plugin
                $options['plugin'] = array(
                    'tag' => $hash[1]
                );
                break;
        }
        
        if ($options) {
            $post_model = new blogPostModel();
            $posts = $post_model->search($options)->fetchSearch($offset, $limit);
        } else {
            $posts = array();
        }
        
        $this->response['count'] = count($posts);
        $this->response['offset'] = $offset;
        $this->response['limit'] = $limit;
        $this->response['posts'] = array_values($posts);
    }
}