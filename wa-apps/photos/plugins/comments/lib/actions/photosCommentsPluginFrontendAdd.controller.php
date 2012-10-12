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
            $auth_provider = waRequest::post('auth_provider', 'guest', waRequest::TYPE_STRING_TRIM);
            if ($auth_provider == 'user' || !$auth_provider) {
                $auth_provider = 'guest';
            }

            $name  = waRequest::post('name', '',  waRequest::TYPE_STRING_TRIM);
            $email = waRequest::post('email', '', waRequest::TYPE_STRING_TRIM);
            $site  = waRequest::post('site', '',  waRequest::TYPE_STRING_TRIM);

            if ($auth_provider != 'guest') {
                $user_data = $this->getStorage()->get('auth_user_data');
                $name = $user_data['name'];
                $email = '';
                $site = $user_data['url'];
            } else {
                wa()->getStorage()->del('auth_user_data');
            }
            return array(
                'contact_id'=>0,
                'name' => $name,
                'email' => $email,
                'site' => $site,
                'auth_provider' => $auth_provider
            );
        }
    }

    protected function getResponseAuthorData()
    {
        if ($this->author->isAuth()) {
            return parent::getResponseAuthorData();
        } else {
            $author = array(
                'name'  => $this->added_comment['name'],
                'email' => $this->added_comment['email'],
                'site'  => $this->added_comment['site']
            );
            if ($this->added_comment['auth_provider'] != 'guest') {
                $author['photo'] = photosCommentModel::getAuthProvoderIcon($this->added_comment['auth_provider']);
            } else {
                $author['photo'] = $this->author->getPhoto(photosCommentModel::SMALL_AUTHOR_PHOTO_SIZE);
            }
            return $author;
        }
    }
}