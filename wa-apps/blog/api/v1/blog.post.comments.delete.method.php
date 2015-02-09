<?php

class blogPostCommentsDeleteMethod extends waAPIMethod
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

        $user_id = wa()->getUser()->getId();
        
        $comment_model = new blogCommentModel();
        $post_model = new blogPostModel();
        
        $comments = $comment_model->getByField('id', $id, 'id');
        $post_ids = array();
        foreach ($comments as $comment) {
            $post_ids[] = $comment['post_id'];
        }
        $post_ids = array_unique($post_ids);
        
        $posts = $post_model->getByField('id', $post_ids, 'id');
        
        $available = array();
        foreach ($comments as $comment) {
            try {
                $rights = blogHelper::checkRights($comment['blog_id'], $user_id, blogRightConfig::RIGHT_READ_WRITE);
            } catch (Exception $e) {
                continue;
            }
            if ($rights == blogRightConfig::RIGHT_READ_WRITE && ($user_id != $posts[$comment['post_id']]['contact_id'])) {
                continue;
            }
            if ($comment['status'] == blogCommentModel::STATUS_DELETED) {
                continue;
            }
            $available[] = $comment['id'];
        }

        $comment_model->updateById($available, array('status' => blogCommentModel::STATUS_DELETED));
        $this->response = true;
    }
}