<?php

class photosAlbumCountModel extends waModel
{
    protected $table = "photos_album_count";

    public function getCount($id)
    {
        $row = $this->getByField(array(
            'album_id' => $id,
            'contact_id' => waSystem::getInstance()->getUser()->getId()
        ));
        if ($row) {
            return $row['count'];
        }
        return null;
    }

    public function getAlbumsWithoutCalculatedCount()
    {
        $sql = "SELECT a.id, ac.album_id FROM photos_album a LEFT JOIN {$this->table} ac ON a.id = ac.album_id AND ac.contact_id = ".wa()->getUser()->getId().
                " WHERE ac.album_id IS NULL";
        return array_keys($this->query($sql)->fetchAll('id'));
    }
}