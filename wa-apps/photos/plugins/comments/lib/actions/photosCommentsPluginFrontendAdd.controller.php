<?php

class photosCommentsPluginFrontendAddController extends photosCommentAddController
{
    private $guest_author = array();

    public function execute() {
        $this->template = $this->getPluginRoot().'/templates/FrontendComment.html';
        parent::execute();
    }

    protected function getContactData()
    {
        if ($this->author->isAuth()) {
            return parent::getContactData();
        } else {
            $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
            $email = waRequest::post('email', '', waRequest::TYPE_STRING_TRIM);
            $site = waRequest::post('site', '', waRequest::TYPE_STRING_TRIM);
            return array(
                'contact_id'=>0,
                'name' => $name,
                'email' => $email,
                'site' => $site
            );
        }
    }

    protected function getResponseAuthorData()
    {
        if ($this->author->isAuth()) {
            return parent::getResponseAuthorData();
        } else {
            return array(
                'name' => $this->added_comment['name'],
                'email' => $this->added_comment['email'],
                'site' => $this->added_comment['site']
            );
        }
    }
}