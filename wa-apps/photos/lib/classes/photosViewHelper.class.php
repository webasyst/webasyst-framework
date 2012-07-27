<?php

class photosViewHelper extends waAppViewHelper
{
    /**
     *
     * Get data array from photos collection
     * @param string $hash selector hash
     * @param int|string $size numerical size or size name
     * @return array
     */
    public function photos($hash, $size = null)
    {
        $size = !is_null($size) ? $size : photosPhoto::getThumbPhotoSize();
        $collection = new photosCollection($hash);
        return $collection->getPhotos("*,thumb_".$size, 0, 500, true);
    }

    /**
     *
     * Get photo data by id
     * @param int $id
     * @param int|string $size numerical size or size name
     * @return array
     */
    public function photo($id, $size = null)
    {
        $id = max(1,intval($id));
        return array_shift($this->photos("id/{$id}", $size));
    }

    public function option($name)
    {
        return wa('photos')->getConfig()->getOption($name);
    }

    /**
     *
     * Get photos albums tree
     * @return string
     */
    public function albums()
    {
        $album_model = new photosAlbumModel();
        $albums = $album_model->getAlbums(true);
        $tree = new photosViewTree($albums);
        return $tree->display('frontend');
    }

    /**
     *
     * Get photos tags list
     * @return array
     */
    public function tags()
    {
        $photo_tag_model = new photosTagModel();
        $cloud = $photo_tag_model->getCloud();
        foreach ($cloud as &$tag) {
            $tag['name'] = photosPhoto::escape($tag['name']);
        }
        unset($tag);
        return $cloud;
    }

    /**
     * Get image with special predefined attributes needed for RIA UI in frontend
     *
     * @param array $photo
     * @param string $size
     * @param array $attributes user-attribure, e.g. class or style
     */
    public function getImgHtml($photo, $size, $attributes = array())
    {
        $attributes['data-size'] = $size;
        $attributes['data-photo-id'] = $photo['id'];
        $attributes['class'] = !empty($attributes['class']) ? $attributes['class'] : '';
        $attributes['class'] .= ' photo';    // !Important: obligatory class. Need in frontend JS
        return photosPhoto::getEmbedImgHtml($photo, $size, $attributes);
    }
}
