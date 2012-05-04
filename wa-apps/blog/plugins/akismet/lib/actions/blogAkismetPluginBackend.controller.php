<?php

class blogAkismetPluginBackendController extends waJsonController
{
    public function execute()
    {
        $comment_id = (int)waRequest::post('spam');
        $comment_model = new blogCommentModel();
        $comment = $comment_model->getById($comment_id);
        $this->response['status'] = null;
        if ($comment) {
            $comment_model->updateById($comment_id, array('akismet_spam' => 1, 'status' => blogCommentModel::STATUS_DELETED));
            $this->response['status'] = blogCommentModel::STATUS_DELETED;

            $blog_plugin = wa()->getPlugin('akismet');

            $akismet = new Akismet(wa()->getRouting()->getUrl('blog', array(), true), $blog_plugin->getSettingValue('api_key'));
            $akismet->setCommentAuthor($comment['name']);
            $akismet->setCommentAuthorEmail($comment['email']);
            $akismet->setCommentContent($comment['text']);

            if (!waSystemConfig::isDebug() && $blog_plugin->getSettingValue('send_spam')) {
                $akismet->submitSpam();
            }
        }
    }
}

