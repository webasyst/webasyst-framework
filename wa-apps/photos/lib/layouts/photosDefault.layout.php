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

        $top_level_albums_count = 0;
        foreach($albums as $a) {
            if (!$a['parent_id']) {
                $top_level_albums_count++;
            }
        }

        /**
         * Extend photo toolbar in photo-page
         * Add extra item to toolbar or add extra menu(s)
         * @event backend_photo_toolbar
         * @return array[string][string]string $return[%plugin_id]['top'] insert own menu(s) in top of photo toolbar
         * @return array[string][string]string $return[%plugin_id%]['edit_menu'] Extra item for edit_menu in photo_toolbar
         * @return array[string][string]string $return[%plugin_id%]['share_menu'] Extra item for edit_menu in photo_toolbar
         * @return array[string][string]string $return[%plugin_id]['bottom'] insert own menu(s) in bottom of photo toolbar
         */
        $this->view->assign('backend_photo_toolbar', wa()->event('backend_photo_toolbar'));

        $tree = new photosViewTree($albums);
        $this->view->assign('albums', $tree->display());
        $this->view->assign('albums_count', count($albums));
        $this->view->assign('top_level_albums_count', $top_level_albums_count);

        $this->view->assign('app_albums', self::getAppAlbums());

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
        $this->view->assign('cloud', $photo_tag_model->getCloud());
        $this->view->assign('popular_tags', $photo_tag_model->popularTags());

        $this->view->assign('rights', array(
            'upload' => $this->getRights('upload'),
            'edit' => $this->getRights('edit')
        ));

        $config = $this->getConfig();
        $this->view->assign('big_size', $config->getSize('big'));
        $this->view->assign('sidebar_width', $config->getSidebarWidth());

        $this->view->assign('map_options', $this->getMapOptions());
    }

    public static function getAppAlbums($force_app_ids=array())
    {
        $photo_model = new photosPhotoModel();
        $apps = wa()->getApps();

        $result = array();
        $counts = $photo_model->countAllByApp();
        $counts += array_fill_keys((array)$force_app_ids, 0);

        $force_app_ids = array_fill_keys((array)$force_app_ids, true);

        foreach($counts as $app_id => $count) {

            // Check that app exists and check access rights, unless app is forced to be present in the result
            if (empty($force_app_ids[$app_id])) {
                if ($count <= 0 || empty($apps[$app_id]) || !wa()->getUser()->getRights($app_id, 'backend')) {
                    continue;
                }
            }

            if (!empty($apps[$app_id])) {
                $name = $apps[$app_id]['name'];
                if (!empty($apps[$app_id]['icon'][16])) {
                    $icon = $apps[$app_id]['icon'][16];
                } else {
                    $icon = reset($apps[$app_id]['icon']);
                }
            } else {
                $name = $app_id;
                $icon = $apps['photos']['icon'][16];
            }
            if ($icon) {
                $icon = wa()->getConfig()->getRootUrl().$icon;
            }

            $result[$app_id] = array(
                'id' => $app_id,
                'name' => $name,
                'count' => $count,
                'icon' => $icon,
            );
        }

        return $result;
    }

    protected function getMapOptions()
    {
        $map_options = array(
            'type' => '',
            'key' => '',
            'locale' => ''
        );

        try {
            $map = wa()->getMap();
            if ($map->getId() === 'google') {
                $map_options = array(
                    'type' => $map->getId(),
                    'key' => $map->getSettings('key'),
                    'locale' => wa()->getLocale()
                );
            } elseif ($map->getId() === 'yandex') {
                $map_options = array(
                    'type' => $map->getId(),
                    'key' => $map->getSettings('apikey'),
                    'locale' => wa()->getLocale()
                );
            }
        } catch (waException $e) {

        }

        return $map_options;
    }
}
