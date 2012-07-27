<?php

class photosPhotoExifModel extends waModel
{
    protected $table = "photos_photo_exif";

    /**
     * Returns exif data of the photo
     *
     * @param int $photo_id
     * @return array
     */
    public function getByPhoto($photo_id)
    {
        $sql = "SELECT name, value FROM ".$this->table." WHERE photo_id = ".(int)$photo_id;
        return $this->query($sql)->fetchAll('name', true);
    }

    /**
     * Save exif data of the photo
     *
     * @param int $photo_id
     * @param array $data - array('FocalLength' => $flentgh,'GPSLatitude' => $lat, ...)
     * @return bool|resource
     */
    public function save($photo_id, $data)
    {
        $values = array();
        foreach ($data as $k => $v) {
            $values[] = array('photo_id' => $photo_id, 'name' => $k, 'value' => is_float($v) ? str_replace(',', '.', $v) : $v);
        }
        if ($values) {
            return $this->multiInsert($values);
        }
        return true;
    }
}