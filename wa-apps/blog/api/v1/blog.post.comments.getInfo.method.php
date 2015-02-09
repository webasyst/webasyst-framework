<?php

class blogPostCommentsGetInfoMethod extends waAPIMethod
{
    protected $method = 'GET';

    public function execute()
    {
        $id = $this->get('id', true);
        
        $comment_model = new blogCommentModel();
        $comment = $comment_model->getById($id);
        if ($comment) {
            $this->response = $comment;
        } else {
            throw new waAPIException('invalid_param', 'Comment not found', 404);
        }
    }
}
