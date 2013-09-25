<?php

class photosCommentsPlugin extends photosPlugin
{
    public function backendPhoto($photo_id)
    {
        $options = array(
            'author' => true,
        );

        $this->sidebarCounters();

        $counters = $this->getLastCounters($photo_id);

        $this->view()->assign(array(
            'photo_id'=> $photo_id,
            'comments_author'=> photosCommentModel::getAuthorInfo(wa()->getUser()->getId()),
            'comments'=> $this->comment()->getFullTree($photo_id, $options),
            'photo_comments_new_count'=> (isset($counters[$photo_id]) ? $counters[$photo_id] : 0),
            'contact_rights' => wa()->getUser()->getRights('contacts', 'backend')
        ));
        return array(
            'bottom' => $this->view()->fetch($this->path.'/templates/BackendPhoto.html'),
        );
    }


    public function backendSidebar()
    {
        $this->sidebarCounters();
        return array(
            'menu' => $this->view()->fetch($this->path.'/templates/BackendSidebar.html')
        );
    }


    public function sidebarCounters()
    {
        static $counter;
        if (!$counter) {
            $counter = array();
            $counter['new'] = $this->getLastCounters();
            $counter['all'] = $this->comment()->getCounters();
        }

        $this->view()->assign('comments_count', $counter['all']);
        $this->view()->assign('comments_count_new', $counter['new']);
    }


    public function backendMakeStack($stack)
    {
        foreach ($stack as $parent_id => $photo_ids) {
            $this->comment()->updateByField('photo_id', $photo_ids, array(
                'photo_id' => $parent_id,
            ));
        }
    }


    private function assets()
    {
        $this->addJs('js/common.js?'.wa()->getVersion());
        $this->addCss('css/comments.css?'.wa()->getVersion());
    }


    public function backendAssets()
    {
        $this->assets();
        return $this->view()->fetch($this->path.'/templates/BackendAssets.html');
    }


    public function frontendAssets()
    {
        $this->assets();
        $this->addJs('js/frontend.js?'.wa()->getVersion());
    }


    public function frontendPhoto($photo)
    {
        $this->view = $this->view();
        $photo_model = new photosPhotoModel();
        $photo_id = $photo['id'];
        if ($parent_id = $photo_model->getStackParentId($photo)) {
            $photo_id = $parent_id;
        }
        $comments = $this->comment()->getFullTree($photo_id, array(
            'author' => true
        ));

        $storage = wa()->getStorage();

        $adapters = array();
        $current_auth = null;
        $current_auth_source = null;

        $user = wa()->getUser();
        if ($user->isAuth()) {
            $comment_author = photosCommentModel::getAuthorInfo($user->getId(), photosCommentModel::BIG_AUTHOR_PHOTO_SIZE);
            $storage->del('auth_user_data');
            $this->view->assign('comment_author', $comment_author);
        } else {

            $current_auth = $storage->read('auth_user_data');

            $adapters = wa()->getAuthAdapters();
            if (!$adapters && $current_auth) {
                $current_auth = null;
                $storage->del('auth_user_data');
            }

            $current_auth_source = $current_auth ? $current_auth['source'] : null;

            $this->view->assign('comment_author', null);
        }

        $app_url = wa()->getAppStaticUrl();

        $this->view->assign('current_auth_source', $current_auth_source);
        $this->view->assign('current_auth', $current_auth);

        $this->view->assign('auth_adapters', $adapters);
        $this->view->assign('require_authorization', $this->getSettings('require_authorization'));
        $this->view->assign('comments', $comments);
        $this->view->assign('photo_id', $photo_id);

        return array(
            'bottom' => $this->view()->fetch($this->path.'/templates/FrontendPhoto.html')
        );
    }


    public function preparePhotosBackend(&$photos)
    {
        $photo_ids = array();
        foreach ($photos as $photo) {
            if (!is_array($photo)) {
                $photo_id = $photo;
            } else {
                $photo_id = $photo['id'];
            }
            $photo_ids[] = $photo_id;
        }
        $count = $this->comment()->getCounters(null, $photo_ids);
        $new = $this->getLastCounters();

        foreach ($photos as &$photo) {
            if (isset($count[$photo['id']])) {
                $all_count = _wp("%d comment","%d comments",$count[$photo['id']]);
                $new_count = isset($new[$photo_id]) ? "+{$new[$photo['id']]}" : '';
                $photo['hooks']['thumb'][$this->id] = <<<HTML
<p>
    <a href="#{%#o.hash%}/photo/{$photo['id']}/" class="small">{$all_count}</a>
    <strong class="small highlighted">{$new_count}</strong>
</p>
HTML;
            }
        }
        unset($photo);
    }


    public function preparePhotosFrontend(&$photos)
    {
        $photo_ids = array();
        foreach ($photos as $photo) {
            if (!is_array($photo)) {
                $photo_id = $photo;
            } else {
                $photo_id = $photo['id'];
            }
            $photo_ids[] = $photo_id;
        }
        $count = $this->comment()->getCounters(null, $photo_ids);

        foreach ($photos as &$photo) {
            if (isset($count[$photo['id']]) && isset($photo['frontend_link'])) {
                $all_count = _wp("%d comment","%d comments",$count[$photo['id']]);
                $photo['hooks']['thumb'][$this->id] = <<<HTML
<p>
    <a href="{$photo['frontend_link']}" class="small">{$all_count}</a>
</p>
HTML;
            }
        }
        unset($photo);
    }


    public function backendPhotoDelete($photo_id)
    {
        $this->comment()->deleteByField('photo_id', $photo_id);
    }

    public function contactsLinks($params)
    {
        waLocale::loadByDomain('photos');
        // TODO: take a look to other models related with contacts

        $links = array();
        $comments_model = new photosCommentModel();
        foreach ($params as $contact_id) {
            $links[$contact_id] = array();
            if ($count = $comments_model->countByField('contact_id', $contact_id)) {
                $links[$contact_id][] = array(
                    'role' => _wd('photos', 'Comments author'),
                    'links_number' => $count,
                );
            }
        }
        
        return $links;
    }
    
    public function contactsDelete($contact_ids)
    {
        $c = new waContactsCollection('id/' . implode(',', $contact_ids));
        $contacts = $c->getContacts('name,phone,email');
        foreach ($contacts as &$contact) {
            if (is_array($contact['phone'])) {
                $phone = reset($contact['phone']);
                if (is_array($phone)) {
                    if (isset($phone['value'])) {
                        $phone = $phone['value'];
                    } else {
                        $phone = '';
                    }
                }
                $contact['phone'] = $phone;
            }
            if (is_array($contact['email'])) {
                $email = reset($contact['email']);
                $contact['email'] = $email;
            }
        }
        
        $comment_model = new photosCommentModel();
        foreach ($contacts as $contact) {
            // Insert contact name into their reviews
            $comment_model->updateByField('contact_id', $contact['id'], array(
                'contact_id' => 0,
                'name' => $contact['name'],
                'email' => $contact['email'],
                'auth_provider' => null
            ));
        }
    }

    /**
     *
     * @return photosCommentModel
     */
    private function comment()
    {
        static $comment_model;
        if (!$comment_model) {
            $comment_model = new photosCommentModel();
        }
        return $comment_model;
    }

    private function view()
    {
        static $view;
        if (!$view) {
            $view = wa()->getView();
        }
        return $view;
    }


    /**
     *
     * @param int $photo_id
     */
    private function getLastCounters($photo_id = null)
    {
        $last_login_datetime = wa()->getConfig()->getLastLoginTime();
        return $this->comment()->getCounters($last_login_datetime, $photo_id);
    }
}