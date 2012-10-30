<?php

class photosFrontController extends waFrontController
{
    public function execute($plugin = null, $module = null, $action = null, $default = false)
    {
        try {
            if (!waRequest::param('page_id')) {
                if (!waRequest::isXMLHttpRequest()) {
                    $request_url = parse_url($this->system->getRootUrl().$this->system->getConfig()->getRequestUrl());
                    if (!empty($request_url['path']) && empty($request_url['query']) && (substr($request_url['path'], -1) != '/' )) {
                        $request_url['path'].='/';
                        $this->system->getResponse()->redirect(implode('',$request_url),301);
                    }
                }
            }
            if ($module == 'frontend' && $action != 'album') {
                // request params
                $id = waRequest::param('id', '', waRequest::TYPE_STRING_TRIM);
                $tag = waRequest::param('tag', '', waRequest::TYPE_STRING_TRIM);
                $author = waRequest::param('author', '', waRequest::TYPE_INT);
                $search = waRequest::param('search', '', waRequest::TYPE_STRING_TRIM);
                $favorites = waRequest::param('favorites', '', waRequest::TYPE_STRING_TRIM);
                $url = waRequest::param('url', '', waRequest::TYPE_STRING_TRIM);

                $type = 'all';
                $hash = '';
                $album = null;
                if ($author) {
                    $hash = 'author/'.$author;
                    $type = 'author';
                } else if ($search) {
                    $hash = 'search/'.$search;
                    $type = 'search';
                } else if ($id) {
                    $hash = 'id/'.$id;
                    $type = 'id';
                } else if ($tag) {
                    $hash = 'tag/'.$tag;
                    $type = 'tag';
                } else if ($favorites) {
                    $hash = 'favorites';
                    $type = 'favorites';
                } else if ($url) {
                    if (preg_match('/^([^\s]+)\/([^\s\/]+)/', trim($url, '/'), $m)) {
                        $album_url = $m[1];
                        $url = $m[2];
                        $hash = photosCollection::frontendAlbumUrlToHash($album_url, $album);
                        if (!$album) {
                            throw new waException(_w('Page not found'), 404);
                        }
                        $type = 'album';
                    }
                }

                $url = rtrim($url, '/');
                waRequest::setParam('url', $url);
                waRequest::setParam('album', $album);
                waRequest::setParam('hash', $hash);
                waRequest::setParam('type', $type);
            }
            parent::execute($plugin, $module, $action, $default);
        } catch(Exception $e) {
            if ($module == 'frontend') {
                parent::execute(null, 'frontend', 'error');
            } else {
                throw $e;
            }
        }
    }
}