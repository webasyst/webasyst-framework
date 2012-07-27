<?php

class photosCommentsPluginBackendAddController extends photosCommentAddController
{
    public function execute() {
        $this->template = $this->getPluginRoot().'/templates/Comment.html';
        parent::execute();
    }

    protected function getContactData()
    {
        if (!$this->author->isAuth()) {
            throw new waException(_w('Access denied'));
        }
        return parent::getContactData();
    }
}