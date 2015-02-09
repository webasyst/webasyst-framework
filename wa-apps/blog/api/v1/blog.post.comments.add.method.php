<?php

class blogPostCommentsAddMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $data = waRequest::post();
        $exclude = array('left_key', 'right_key', 'type', 'full_url', 'parent_id');
        foreach ($exclude as $k) {
            if (isset($data[$k])) {
                unset($data[$k]);
            }
        }
        
        // check required params
        $this->post('text', true);
        
        $post_id = $this->get('post_id', true);
        $post_model = new blogPostModel();
        $post = $post_model->getBlogPost($post_id);
        if (!$post) {
            throw new waAPIException('invalid_param', 'Post not found', 404);
        }
        
        $parent_id = $this->post('parent_id');
        $comment_model = new blogCommentModel();
        if ($parent_id) {
            $parent = $comment_model->getById($parent_id);
            if (!$parent) {
                throw new waAPIException('invalid_param', 'Parent comment not found', 404);
            }
        }
        
        $contact_id = wa()->getUser()->getId();

        // check rights
        try {
            blogHelper::checkRights($post['blog_id'], $contact_id, blogRightConfig::RIGHT_READ);
        } catch (waException $e) {
            throw new waAPIException('access_denied', 403);
        }
        
        // check comment mode
        if (!$post['comments_allowed']) {
            throw new waAPIException('invalid_param', "Isn't allowed comment to this post", 404);
        }
        
        $data = array_merge($data, array(
            'blog_id' => $post['blog_id'],
            'post_id' => $post_id,
            'contact_id' => $contact_id,
            'auth_provider' => blogCommentModel::AUTH_USER
        ));
        
        $messages = $comment_model->validate($data);
        if ($messages) {
            throw new waAPIException('invalid_param', 'Validate messages: ' . implode("\n", $messages), 404);
        }
        
        $id = $comment_model->add($data, $parent_id);
        $_GET['id'] = $id;
        $method = new blogPostCommentsGetInfoMethod();
        $this->response = $method->getResponse(true);
        
    }
}