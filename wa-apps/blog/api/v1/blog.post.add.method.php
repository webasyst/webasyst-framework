<?php

class blogPostAddMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $data = waRequest::post();
        
        // check required params
        $this->post('blog_id', true);
        $this->post('title', true);
        
        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable();
        if (!isset($blogs[$data['blog_id']])) {
            throw new waAPIException('invalid_param', 'Blog not found', 404);
        }
        $blog = $blogs[$data['blog_id']];
        if ($blog['rights'] < blogRightConfig::RIGHT_READ_WRITE) {
            throw new waAPIException('access_denied', 403);
        }
        
        $data = array_merge($data, array(
            'blog_status' => $blog['status'],
            'url' => '',
            'text' => '',
            'status' => blogPostModel::STATUS_PUBLISHED
        ));
        
        $post_model = new blogPostModel();

        $options = array();
        if (waRequest::post('transliterate', null)) {
            $options['transliterate'] = true;
        }

        $messages = $post_model->validate($data, array('transliterate' => true));
        
        if ($messages) {
            throw new waAPIException('invalid_param', 'Validate messages: ' . implode("\n", $messages), 404);
        }
        
        $id = $post_model->updateItem(null, $data);
        $_GET['id'] = $id;
        $method = new blogPostGetInfoMethod();
        $this->response = $method->getResponse(true);
        
    }
}