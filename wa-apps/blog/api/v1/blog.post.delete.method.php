<?php

class blogPostDeleteMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $id = $this->post('id', true);
        if (!is_array($id)) {
            if (strpos($id, ',') !== false) {
                $id = array_map('intval', explode(',', $id));
            } else {
                $id = array($id);
            }
        }

        $post_model = new blogPostModel();
        $blog_model = new blogBlogModel();
        $blogs = $blog_model->getAvailable(wa()->getUser(),'id');
        $post_model->deleteByField(array('id' => $id, 'blog_id' => array_keys($blogs)));
        $this->response = true;
    }
}