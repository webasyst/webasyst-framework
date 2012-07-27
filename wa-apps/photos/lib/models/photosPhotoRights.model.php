<?php

class photosPhotoRightsModel extends waModel
{
    protected $table = 'photos_photo_rights';

    /**
     * @param int $photo_id
     * @param boolean $check_edit
     * @return boolean
     */
    public function checkRights($photo_id, $check_edit = false)
    {
        $user = wa()->getUser();
        if ($check_edit) {
            $photo_model = new photosPhotoModel();
            $photo = $photo_model->getById($photo_id);
            if (!$photo) {
                return false;
            }
            if ($check_edit && $photo['contact_id'] != $user->getId() && !$user->getRights('photos', 'edit')) {
                return false;
            }
        }
        if ($user->isAdmin()) {
            $where = "(group_id >= 0 OR group_id = -".(int)$user->getId().")";
        } else {
            $groups = $user->getGroupIds();
            $where = "group_id IN ('".implode("','", $groups)."')";
        }
        $sql = "SELECT count(*) FROM ".$this->table."
                WHERE photo_id = ".(int)$photo_id." AND ".$where."
                LIMIT 1";
        return (bool)$this->query($sql)->fetchField();
    }

    /**
     * @param array $photo_ids array of photo's id
     * @param boolean $check_edit
     * @return array array of photo's id
     */
    public function filterAllowedPhotoIds($photo_ids, $check_edit = false)
    {
        if (!$photo_ids) {
            return array();
        }
        $join = '';
        $where = $this->getWhereByField('photo_id', (array)$photo_ids);

        $user = wa()->getUser();
        if ($user->isAdmin()) {
            $where .= " AND (group_id >= 0 OR group_id = -".(int)$user->getId().")";
        } else {
            $groups = $user->getGroupIds();
            $where .= " AND group_id IN ('".implode("','", $groups)."')";
        }
        if ($check_edit && !$user->getRights('photos', 'edit')) {
            $join = " INNER JOIN photos_photo p ON p.id = pr.photo_id AND p.contact_id = ".$user->getId();
        }

        $sql = "SELECT pr.photo_id FROM ".$this->table." pr $join
                WHERE $where";
        return array_keys($this->query($sql)->fetchAll('photo_id'));
    }
}