<?php

class blogCommentsEditController extends waJsonController
{
    public function execute()
    {
        $comment_id = waRequest::post('id', null, waRequest::TYPE_INT);
        $status = waRequest::post('status', null, waRequest::TYPE_STRING);

        $comment_model = new blogCommentModel();
        $post_model = new blogPostModel();

        $user_id = $this->getUser()->getId();
        $comment = $comment_model->getById($comment_id);

        if (!$comment) {
            throw new waException(_w('Comment not found'), 404);
        }

        $post = $post_model->getBlogPost(array('id'=>$comment['post_id'], 'blog_id'=>$comment['blog_id']));

        if (!$post) {
            throw new waException(_w('Post not found'), 404);
        }

        $rights = blogHelper::checkRights($comment['blog_id'], $user_id, blogRightConfig::RIGHT_READ_WRITE);
        if ($rights == blogRightConfig::RIGHT_READ_WRITE && ($user_id != $post['contact_id'])) {
            throw new waRightsException(_w('Access denied'), 403);
        }

        $changed = $comment_model->changeStatus($comment_id, $status);
        $count = $comment_model->getCount($comment['blog_id'], $comment['post_id']);

        $this->response = array(
            'count_str' => $count." "._w('comment', 'comments', $count),
            'status'    => $status,
            'changed'   => $changed,
        );
    }


}