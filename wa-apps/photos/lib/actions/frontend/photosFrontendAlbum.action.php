<?php

class photosFrontendAlbumAction extends photosFrontendCollectionViewAction
{
    public $photo_url;

    private $album;

    private $album_url;

    public function execute()
    {
        $this->init();

        $url = trim(waRequest::param('url', '', waRequest::TYPE_STRING_TRIM), '/');
        if (!$url) {
            throw new waException(_w('Page not found', 404));
        }

        $this->route($url);
        if (!$this->album) {
            throw new waException(_w('Page not found', 404));
        }

        $this->album = photosFrontendAlbum::escapeFields($this->album);

        // retrieve user params
        $album_params_model = new photosAlbumParamsModel();
        $params = $album_params_model->get($this->album['id']);
        $params = photosPhoto::escape($params);
        $this->album += $params;

        $breadcrumbs = $this->album_model->getBreadcrumbs($this->album['id'], true);

        if ($breadcrumbs) {
            $root_album = reset($breadcrumbs);
            $root_album_id = $root_album['album_id'];
        } else {
            $root_album_id = $this->album['id'];
        }
        $this->view->assign('root_album_id', $root_album_id);
        if ($this->layout) {
            $this->layout->assign('root_album_id', $root_album_id);
        }

        $child_albums = $this->view->getHelper()->photos->childAlbums($this->album['id']);

        waRequest::setParam('breadcrumbs', $breadcrumbs);
        waRequest::setParam('nofollow', $this->album['status'] <= 0 ? true : false);
        waRequest::setParam('disable_sidebar', true);

        $this->setThemeTemplate('album.html');
        $this->view->assign('album', $this->album);
        $this->view->assign('childcrumbs', $child_albums);
        $this->view->assign('child_albums', $child_albums);

        $this->getResponse()->addJs('js/common.js?v='.wa()->getVersion(), true);
        $this->finite();
    }

    protected function route($url)
    {
        $hash = photosCollection::frontendAlbumUrlToHash($url, $album);
        if (!$album) {
            if (preg_match('/^([^\s]+)\/([^\s\/]+)/', trim($url, '/'), $m)) {
                $album_url = $m[1];
                $photo_url = $m[2];
                $hash = photosCollection::frontendAlbumUrlToHash($album_url, $album);
                $this->photo_url = $photo_url;
                $this->album_url = $album_url;
            }
        } else {
            $this->album_url = $url;
        }
        $this->album = $album;
        $this->hash = $hash;
    }

    protected function workupPhotos(&$photos)
    {
        $renderer = new photosPhotoHtmlRenderer($this->getTheme());
        $photo_model = new photosPhotoModel();

        // parent of current photo (that stacked, i.e. in stack)
        $parent_id = null;
        if ($this->photo_url) {
            $stacked_photo = $photo_model->getByField('url', $this->photo_url);
            if (!$stacked_photo) {
                throw new waException(_w('Page not found', 404));
            }
            $parent_id = $photo_model->getStackParentId($stacked_photo);
        }
        // During going over all photos we also look if some photo is a parent of current stacked photo
        foreach ($photos as &$photo) {

            $photo['stack_nav'] = '';
            $stack = (array)$photo_model->getStack($photo['id'], array('tags' => true));
            if ($stack) {
                foreach ($stack as &$item) {
                    $item['thumb_custom'] = array(
                        'url' => photosPhoto::getPhotoUrlTemplate($item)
                    );
                    $item['full_url'] = photosFrontendAlbum::getLink($this->album).$item['url'].'/';
                }
                unset($item);

                // iterable photo is parent of current stacked photo - replace
                if ($parent_id == $photo['id']) {
                    $photo = $stacked_photo;
                    $photo['full_url'] = photosFrontendAlbum::getLink($this->album).$photo['url'].'/';
                    $photo['stack_nav'] = $renderer->getStackNavigationPanel($stack, $photo);
                } else {
                    $photo['stack_nav'] = $renderer->getStackNavigationPanel($stack, $photo);
                }
            }
            $photo['frontend_link'] = photosFrontendPhoto::getLink($photo, array('full_url' => $this->album_url));
        }
        unset($photo);
    }
}