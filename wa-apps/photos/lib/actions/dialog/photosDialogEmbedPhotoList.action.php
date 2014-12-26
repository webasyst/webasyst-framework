<?php

class photosDialogEmbedPhotoListAction extends waViewAction
{
    public function execute()
    {
        $photo_ids = waRequest::get('photo_ids', '', waRequest::TYPE_STRING_TRIM);
        $size = waRequest::get('size', null, waRequest::TYPE_STRING_TRIM);
        $hash = waRequest::get('hash', '', waRequest::TYPE_STRING_TRIM);

        if (strstr($hash, 'search') !== false) {
            $hash = urldecode($hash);
        }

        $sizes = $this->getConfig()->getSizes();

        if (!$size || in_array($size, $sizes) === false) {
            $size = current($sizes);
        }

        $photo_model = new photosPhotoModel();
        $limit = $photo_model->countAll();
        $entire_context['all']['count'] = $limit;
        if (strstr($hash, 'album') !== false) {
            $album_collection = new photosCollection($hash);
            $limit = $album_collection->count();
            $entire_context['album']['count'] = $limit;
        } else if (strstr($hash, 'tag') !== false) {
            $tag_collection = new photosCollection($hash);
            $limit = $tag_collection->count();
            $tag = rtrim(end(explode('/', $hash)), '/');
            $entire_context['tag'] = array(
                'count' => $limit,
                'tag' => $tag
            );
        } else if (strstr($hash, 'rate') !== false) {
            $rate_collection = new photosCollection($hash);
            $limit = $rate_collection->count();
            $entire_context['rate']['count'] = $limit;
        }

        if (!$photo_ids &&
            strstr($hash, 'album') === false &&
            strstr($hash, 'tag') === false &&
            strstr($hash, 'rate') === false)
        {
            $hash = '';
        } else if ($photo_ids) {
            $hash = '/id/'.$photo_ids;
        }

        $context = photosPhoto::getEmbedPhotoListContext($hash, $size, $limit);
        $domains = $context['domains'];
        if (count($domains) <= 1) {
            $domains = array();
        }

        $this->view->assign('sizes', $sizes);
        $this->view->assign('size',  $size);
        $this->view->assign('context', $context);
        $this->view->assign('is_entire', !$photo_ids);
        $this->view->assign('entire_context', $entire_context);
        $this->view->assign('original_domain', wa()->getRootUrl(true));
        $this->view->assign('domains', $domains);
    }
}
