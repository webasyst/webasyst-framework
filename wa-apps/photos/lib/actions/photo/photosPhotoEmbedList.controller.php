<?php

class photosPhotoEmbedListController extends waJsonController
{
    public function execute()
    {
        $photo_ids = waRequest::post('photo_ids', '', waRequest::TYPE_STRING_TRIM);
        $size = waRequest::post('size', null, waRequest::TYPE_STRING_TRIM);
        $hash = waRequest::post('hash', '', waRequest::TYPE_STRING_TRIM);

        if (strstr($hash, 'search') !== false) {
            $hash = urldecode($hash);
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

        if (!$size) {
            throw new waException(_w('Unknown size'));
        }
        $sizes = $this->getConfig()->getSizes();
        if (in_array($size, $sizes) === false) {
            throw new waException(_w('Unknown size'));
        }
        $this->response['context'] = photosPhoto::getEmbedPhotoListContext($hash, $size);
    }
}