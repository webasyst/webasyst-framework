<?php

/**
 * Lazy loading photostream in one-photo page
 * @see photosFrontendPhotoAction
 */
class photosPublicgalleryPluginFrontendLoadListAction extends waViewAction
{
    public function execute()
    {
        $count = $this->getConfig()->getOption('photos_per_page');
        $padding_count = 2;

        $direction = waRequest::get('direction', 1, waRequest::TYPE_INT);
        $url = waRequest::param('url');
        if (!$url) {
            throw new waException(_w('Page not found', 404));
        }

        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getByField('url', $url);

        $real_count = $count;
        if ($photo) {
            $c = new photosCollection('publicgallery/myphotos');
            $offset = $c->getPhotoOffset($photo);

            if ($direction > 0) {
                $offset +=1;    // next photos
            } else {
                $offset -= $real_count;    // prev photos
                if ($offset < 0) {
                    $real_count += $offset;
                    $offset = 0;
                }
            }

            $photo_stream = $c->getPhotos('*,thumb,thumb_crop,tags', $offset, $real_count);

            $photo_stream = photosCollection::extendPhotos($photo_stream);
            foreach ($photo_stream as &$item) {
                $item['thumb_custom'] = array(
                    'url' => photosPhoto::getPhotoUrlTemplate($item)
                );
                $item['full_url'] = photosFrontendPhoto::getLink(array(
                    'url' => $item['url']
                ), $album ? $album : $hash);
            }
            unset($item);
            $real_count = count($photo_stream);
            if ($real_count < $count) {
                if ($direction > 0) {
                    $photo_stream = array_merge(
                        $photo_stream,
                        array_pad(array(), $padding_count, null)
                    );
                } else {
                    $photo_stream = array_merge(
                        array_pad(array(), $padding_count, null),
                        $photo_stream
                    );
                }
            }

            $renderer = new photosPhotoHtmlRenderer($this->getTheme());
            echo $renderer->getPhotoStream($photo_stream, null);
        }
        exit;
    }
}