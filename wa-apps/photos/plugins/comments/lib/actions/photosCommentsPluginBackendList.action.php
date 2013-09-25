<?php

class photosCommentsPluginBackendListAction extends waViewAction
{
    public function execute()
    {
        $count = 10;
        $offset = waRequest::post('offset', 0, waRequest::TYPE_INT);

        $comment_model = new photosCommentModel();
        $comments = $comment_model->getList(array(
            'author' => true,
            'crop' => true,
            'reply_to' => true
        ), $offset, $count);

        $this->view->assign('sidebar_width', wa('photos')->getConfig()->getSidebarWidth());
        $this->view->assign('comments_author', photosCommentModel::getAuthorInfo(wa()->getUser()->getId()));
        $this->view->assign('comments', $comments);
        $this->view->assign('contact_rights', wa()->getUser()->getRights('contacts', 'backend'));

        // get and send to view sidebar counters for updating
        $plugin = wa()->getPlugin('comments');
        $plugin->sidebarCounters();
    }
}