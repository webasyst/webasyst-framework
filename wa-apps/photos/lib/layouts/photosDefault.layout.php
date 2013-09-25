<?php

class photosDefaultLayout extends waLayout
{
    public function execute()
    {

        if ($this->getRights('upload')) {
            $this->executeAction('upload_dialog', new photosUploadAction());
        }

        $album_model = new photosAlbumModel();
        $albums = $album_model->getAlbums();
        /**
         * Extend photo toolbar in photo-page
         * Add extra item to toolbar
         * @event backend_photo_toolbar
         * @return array[string][string]string $return[%plugin_id%]['edit_menu'] Extra item for edit_menu in photo_toolbar
         * @return array[string][string]string $return[%plugin_id%]['share_menu'] Extra item for edit_menu in photo_toolbar
         */
        $this->view->assign('backend_photo_toolbar', wa()->event('backend_photo_toolbar'));


        $tree = new photosViewTree($albums);
        $this->view->assign('albums', $tree->display());

        $collection = new photosCollection();
        $collection_rated = new photosCollection('search/rate>0');

        $this->view->assign('count', $collection->count());
        $this->view->assign('rated_count', $collection_rated->count());
        $this->view->assign('last_login_datetime', $this->getConfig()->getLastLoginTime());

        /**
         * Extend sidebar
         * Add extra item to sidebar
         * @event backend_sidebar
         * @return array[string][string]string $return[%plugin_id%]['menu'] Extra item for menu in sidebar
         * @return array[string][string]string $return[%plugin_id%]['section'] Extra section in sidebar
         */
        $this->view->assign('backend_sidebar', wa()->event('backend_sidebar'));
        /**
         * Include plugins js and css
         * @event backend_assets
         * @return array[string]string $return[%plugin_id%] Extra head tag content
         */
        $this->view->assign('backend_assets', wa()->event('backend_assets'));

        $photo_tag_model = new photosTagModel();
        $cloud = $photo_tag_model->getCloud();
        $this->view->assign('cloud', $cloud);

        $this->view->assign('rights', array(
            'upload' => $this->getRights('upload'),
            'edit' => $this->getRights('edit')
        ));

        $config = $this->getConfig();
        
        $this->view->assign('big_size', $config->getSize('big'));
        $this->view->assign('sidebar_width', $config->getSidebarWidth());
        
        $tag_model = new photosTagModel();
        $this->view->assign('popular_tags', $tag_model->popularTags());
    }
}