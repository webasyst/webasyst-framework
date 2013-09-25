<?php

class photosPublicgalleryPluginFrontendMyphotosAction extends waViewAction
{
    public function execute()
    {
        $lazy = !is_null(waRequest::get('lazy'));
        if (!$lazy) {
            $this->setLayout(new photosDefaultFrontendLayout());
        } else {
            $this->setTemplate('FrontendPhotos');
        }

        $photos_per_page = wa('photos')->getConfig()->getOption('photos_per_page');
        $limit = $photos_per_page;

        $page = 1;

        if ($lazy) {
            $offset = max(0, waRequest::get('offset', 0, waRequest::TYPE_INT));
        } else {
            $page = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
            $offset = ($page - 1)  *  $photos_per_page;
        }

        $c = new photosCollection('publicgallery/myphotos');
        $photos = $c->getPhotos('*', $offset, $limit);
        $photos = photosCollection::extendPhotos($photos);
        
        $v = wa()->getVersion();
        wa('photos')->getResponse()->addJs('js/lazy.load.js?v='.$v, true);
        wa('photos')->getResponse()->addJs('js/frontend.photos.js?v='.$v, true);

        $storage = wa()->getStorage();
        $current_auth = $storage->read('auth_user_data');
        $current_auth_source = $current_auth ? $current_auth['source'] : null;
        $this->view->assign('current_auth', $current_auth,true);
        $adapters = wa()->getAuthAdapters();
        
        $total_count = $c->count();
        $this->view->assign(array(
            'photos' => $photos,
            'page' => $page,
            'offset' => $offset,
            'photos_per_page' => $photos_per_page,
            'total_photos_count' => $total_count,
            'lazy_load' => $lazy,
            'image_upload_url' => wa()->getRouteUrl('photos/frontend/imageUpload'),
            'pages_count' => floor($total_count / $photos_per_page) + 1,
            'current_auth_source' => $current_auth_source,
            'adapters' => $adapters
        ));
    }
}