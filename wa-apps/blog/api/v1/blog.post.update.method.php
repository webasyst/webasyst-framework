<?php

class blogPostUpdateMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute() 
    {
        $id = $this->get('id', true);
        
        $post_model = new blogPostModel();
        $post = $post_model->getById($id);
        if (!$post) {
            throw new waAPIException('invalid_param', 'Post not found', 404);
        }
        
        //check rights
        if (blogHelper::checkRights($post['blog_id']) < blogRightConfig::RIGHT_FULL &&
            $post['contact_id'] != wa()->getUser()->getId())
        {
            throw new waAPIException('access_denied', 403);
        }

        $data = array_merge($post, waRequest::post());
        
        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable();
        if (!isset($blogs[$data['blog_id']])) {
            throw new waAPIException('invalid_param', 'Blog not found', 404);
        }
        $blog = $blogs[$data['blog_id']];
        $data['blog_status'] = $blog['status'];
        $data['datetime'] = $this->formateDatetime($data['datetime']);
        
        $messages = $post_model->validate($data, array('transliterate' => true));
        if ($messages) {
            throw new waAPIException('invalid_param', 'Validate messages: ' . implode("\n", $messages), 404);
        }

        $post_model->updateItem($data['id'], $data);
        $_GET['id'] = $id;
        $method = new blogPostGetInfoMethod();
        $this->response = $method->getResponse(true);
        
    }
    
    public function formateDatetime($datetime)
    {
        return wa_date('date', $datetime) . ' ' . wa_date('H', $datetime) . ':' . wa_date('i', $datetime);
    }
    
}