<?php

class photosPhotoRightsModel extends waModel
{
    protected $table = 'photos_photo_rights';

    /**
     * @param array|int $photo photo or id of photo
     * @param boolean $check_edit
     * @return boolean
     */
    public function checkRights($photo, $check_edit = false)
    {
        if (!is_array($photo)) {
            $photo_model = new photosPhotoModel();
            $photo = $photo_model->getById((int)$photo);
        }
        if (!$photo) {
            return false;
        }
        $photo_id = $photo['id'];
        $user = wa()->getUser();
        if ($check_edit && $photo['contact_id'] != $user->getId() && !$user->getRights('photos', 'edit')) {
            return false;
        }

        if (!empty($photo['app_id'])) {
            return !!$user->getRights($photo['app_id'], 'backend');
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

        $unknown_ids = array();
        foreach((array) $photo_ids as $id) {
            $unknown_ids[$id] = $id;
        }

        $user = wa()->getUser();
        $allowed_ids = array();

        // When a photo belongs to another app, check that app's access permissions
        $by_app = array();
        $sql = "SELECT id, app_id FROM photos_photo WHERE id IN (?) AND app_id IS NOT NULL";
        foreach($this->query($sql, array($photo_ids)) as $row) {
            empty($by_app[$row['app_id']]) && ($by_app[$row['app_id']] = array());
            $by_app[$row['app_id']][] = $row['id'];
        }
        foreach($by_app as $app_id => $ids) {
            if ($user->getRights($app_id, 'backend')) {
                $allowed_ids = array_merge($allowed_ids, $ids);
            }
            foreach($ids as $id) {
                unset($unknown_ids[$id]);
            }
        }

        // Anything else to check?
        if (!$unknown_ids) {
            return $allowed_ids;
        }

        // Check all other photos against `photos_photo_rights` table
        $join = '';
        $where = $this->getWhereByField('photo_id', $photo_ids);

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
        return array_merge($allowed_ids, array_keys($this->query($sql)->fetchAll('photo_id')));
    }
}