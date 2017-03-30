<?php

class photosPhotoRenderHelper
{
    /**
     * theme
     * @var waTheme
     */
    private $theme;

    /**
     * Html-block renderer
     * @var photosPhotoHtmlRenderer
     */
    private $renderer;

    /**
     * Photo
     * @var array
     */
    private $photo;

    /**
     * Parent of photo if photo in stack
     * @var array|null
     */
    private $parent = null;

    /**
     * Stack of if exists
     * @var array|null
     */
    private $stack = null;

    /**
     * Collection's hash
     * @var string
     */
    private $hash = '';

    /**
     * Hash for access to private photo
     * @var null|string
     */
    private $private_hash = null;

    /**
     * Album
     * @var array
     */
    private $album = null;

    /**
     * Url of next photo page
     * @var string|null
     */
    private $next_photo_url = null;

    /**
     * Url of prev photo page
     * @var string|null
     */
    private $prev_photo_url = null;


    private $rendered_blocks = array();

    private $photo_model;

    public function __construct($photo, $private_hash = null)
    {
        // renderer of html blocks
        $this->renderer = new photosPhotoHtmlRenderer($this->getTheme());
        $this->photo_model = new photosPhotoModel();
        $this->private_hash = $private_hash;
        $this->photo = $photo;
    }

    public function workUp($options = array())
    {
        $this->hash = isset($options['hash']) && $options['hash'] !== false ? $options['hash'] : false;
        $this->album = isset($options['album']) && $options['album'] ? $options['album'] : null;
        $need_photo_stream = isset($options['need_photo_stream']) ? $options['need_photo_stream'] : true;

        $this->workUpPhoto();

        if ($need_photo_stream) {
            $this->rendered_blocks['photo_stream'] = $this->renderPhotoStream();
        }

        // addition fields for photo here
        $this->photo['next_photo_url'] = $this->next_photo_url;
        $this->photo['prev_photo_url'] = $this->prev_photo_url;
        $this->rendered_blocks['stack_nav'] = $this->renderStackNavigationPanel();
        $this->rendered_blocks['albums'] = $this->renderAlbums();
        $this->rendered_blocks['tags'] = $this->renderTags();
        $this->rendered_blocks['exif'] = $this->renderExifInfo();
        $this->rendered_blocks['author'] = $this->renderAuthorInfo();

        return array(
            'photo' => $this->photo,
            'blocks' => $this->rendered_blocks
        );
    }

    private function workUpPhoto()
    {
        if ($this->private_hash) {
            $this->photo['private_hash'] = photosPhotoModel::getPrivateUrl($this->photo);
        }
        // get stack
        $this->stack = (array)$this->photo_model->getStack($this->photo['id'], array(
            'tags' => true, 'thumb' => true
        ));
        foreach ($this->stack as &$item) {
            $item['thumb_custom'] = array('url' => photosPhoto::getPhotoUrlTemplate($item));
            if ($this->private_hash) {
                $item['private_url'] = photosPhotoModel::getPrivateUrl($item);
            }
            $item['full_url'] = photosFrontendPhoto::getLink(array(
                'url' => $item['url']
            ), $this->album ? $this->album : $this->hash);
        }
        unset($item);
        // if stack exists extract parent
        if ($this->stack) {
            //TODO: escape stack
            $this->parent = $this->stack[0];
        }
    }

    private function renderAlbums()
    {
        $album_photos_model = new photosAlbumPhotosModel();
        $albums = $album_photos_model->getAlbums($this->photo['id'], array('id', 'name', 'full_url'), true);
        $albums = isset($albums[$this->photo['id']]) ? $albums[$this->photo['id']] : array();

        return $this->renderer->getAlbums($albums);
    }

    private function renderTags()
    {
        // get tags
        $photo_tags_model = new photosPhotoTagsModel();
        $tags = $photo_tags_model->getTags($this->photo['id']);

        return $this->renderer->getTags($tags);
    }

    private function renderExifInfo()
    {
        // exif info
        $exif_model = new photosPhotoExifModel();
        $exif = $exif_model->getByPhoto($this->photo['id']);

        return $this->renderer->getExifInfo($exif);
    }

    private function renderAuthorInfo()
    {
        // author info
        $contact =  new waContact($this->photo['contact_id']);
        $author = array(
            'id' => $contact['id'],
            'name' => $contact['name'],
            'photo_url' => $contact->getPhoto(photosPhoto::AUTHOR_PHOTO_SIZE),
            'photo_upload_datetime' => $this->photo['upload_datetime']
        );

        return $this->renderer->getAuthorInfo($author);
    }

    private function _formPhotoStream($photo)
    {
        $photos_per_page = wa()->getConfig()->getOption('photos_per_page');
        $middle_pos = round($photos_per_page/2);
        $middle_pos = max(10, $middle_pos);
        $padding_count = 2;

        $collection = new photosCollection($this->hash);
        $current_offset = $collection->getPhotoOffset($photo);
        $total_count = $collection->count();

        // offset-bounds of stream-frame
        $left_bound = $current_offset - $middle_pos;
        $right_bound = $current_offset + $middle_pos;

        $head_padding_count = 0;
        if ($left_bound < 0) {
            $head_padding_count = $padding_count;
        }

        $tail_padding_count = 0;
        if ($right_bound > $total_count - 1) {
            $tail_padding_count = $padding_count;
        }

        if (!$head_padding_count) {
            // recalc padding count for head if need
            if ($left_bound < 0) {
                $head_padding_count = $padding_count;
            }
        }
        if (!$tail_padding_count) {
            // recalc padding count for tail if need
            if ($right_bound > $total_count - 1) {
                $tail_padding_count = $padding_count;
            }
        }

        $left_bound = max($left_bound, 0);
        $right_bound = min($right_bound, $total_count - 1);
        $count = $right_bound - $left_bound + 1;

        $photo_stream = $collection->getPhotos('*,thumb,thumb_crop,tags', $left_bound, $count);
        $photo_stream = photosCollection::extendPhotos($photo_stream);
        foreach ($photo_stream as &$item) {
            $item['thumb_custom'] = array(
                'url' => photosPhoto::getPhotoUrlTemplate($item)
            );
            $item['full_url'] = photosFrontendPhoto::getLink(array(
                'url' => $item['url']
            ), $this->album ? $this->album : $this->hash, false);
        }
        unset($item);

        $frame = $this->foundFrame($photo_stream, $photo);
        if (!$frame) {
            throw new waException(_w('Page not found', 404));
        }

        if ($frame['prev']) {
            $this->prev_photo_url = photosFrontendPhoto::getLink($frame['prev'], $this->album ? $this->album : $this->hash, false);
        }
        if ($frame['next']) {
            $this->next_photo_url = photosFrontendPhoto::getLink($frame['next'], $this->album ? $this->album : $this->hash, false);
        }

        // padding with null head of list if need
        if ($head_padding_count) {
            $photo_stream = array_merge(
                array_pad(array(), $head_padding_count, null),    // dummy (nulls) padding
                $photo_stream
            );
        }
        // padding tail if need
        if ($tail_padding_count) {
            $photo_stream = array_merge(
                $photo_stream,
                array_pad(array(), $tail_padding_count, null)
            );
        }
        return $photo_stream;
    }

    /**
     * Found photo in stream, and prev photo and next photo
     * @param $photo_stream
     * @param $photo
     * @return array
     */
    private function foundFrame($photo_stream, $photo)
    {
        list($prev, $next, $current) = array(null, null, null);

        $found = false;

        $photo_stream = array_values($photo_stream);
        $n = count($photo_stream);

        for ($i = 0; $i < $n; $i += 1) {
            $current = $photo_stream[$i];
            $next = null;
            if ($i + 1 < $n) {
                $next = $photo_stream[$i + 1];
            }
            if ($current['id'] == $photo['id']) {
                $found = true;
                break;
            }
            $prev = $current;
        }

        if (!$found) {
            return null;
        }

        return array(
            'prev' => $prev,
            'next' => $next,
            'current' => $current
        );
    }

    private function renderPhotoStream()
    {
        $photo = $this->parent ? $this->parent : $this->photo;
        $photo_stream = $this->_formPhotoStream($photo);
        return $this->renderer->getPhotoStream($photo_stream, $photo);
    }

    private function renderStackNavigationPanel()
    {
        if ($this->stack) {
            return $this->renderer->getStackNavigationPanel($this->stack, $this->photo);
        } else {
            return '';
        }
    }

    private function getTheme()
    {
        if ($this->theme == null) {
            $theme = waRequest::getTheme();
            if (strpos($theme, ':') !== false) {
                list($app_id, $theme) = explode(':', $theme, 2);
            } else {
                $app_id = null;
            }
            $this->theme = new waTheme($theme, $app_id);
        }
        return $this->theme;
    }
}
