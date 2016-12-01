<?php

class teamWaGroupModel extends waGroupModel
{
    const WA_GROUP_TYPE_LOCATION = 'location';
    const WA_GROUP_TYPE_GROUP = 'group';
    /**
     * @var teamLocationModel
     */
    static private $tlm;
    /**
     * @param $group_id
     * @return array|null
     */
    public function getGroup($group_id)
    {
        $group = $this->getById($group_id);
        if (!$group) {
            return $group;
        }

        $lm = $this->getLocationModel();

        $empty_location = $lm->getEmptyRow();
        $empty_location['group_id'] = $group['id'];

        if ($group['type'] != self::WA_GROUP_TYPE_LOCATION) {
            $group['location'] = $empty_location;
        } else {
            $location = $this->getLocationModel()->getById($group['id']);
            if (!$location) {
                $location = $empty_location;
            }
            $group['location'] = $location;
        }

        return $group;
    }

    public function updateGroup($group_id, $data)
    {
        $old_group = $this->getGroup($group_id);
        if (!$old_group) {
            return;
        }

        $group = array();
        foreach ($data as $field => $value) {
            if ($this->fieldExists($field) && $field !== 'id' && $old_group[$field] != $value) {
                $group[$field] = $value;
            }
        }

        if ($group) {
            $this->updateById($group_id, $group);
        }

        $lm = $this->getLocationModel();

        $type = ifset($group['type'], $old_group['type']);
        if ($type !== self::WA_GROUP_TYPE_LOCATION) {
            if ($old_group['type'] == self::WA_GROUP_TYPE_LOCATION) {
                $lm->deleteById($group_id);
            }
            return;
        }

        $location = array();
        foreach ($data as $field => $value) {
            if ($lm->fieldExists($field) && $field !== 'group_id' && !$this->fieldExists($field)) {
                $location[$field] = $value;
            }
        }
        if (isset($data['location']) && is_array($data['location'])) {
            foreach ($data['location'] as $field => $value) {
                if ($lm->fieldExists($field) && $field !== 'group_id') {
                    $location[$field] = $value;
                }
            }
        }

        if ($location) {
            if (!$lm->getById($group_id)) {
                $lm->add($group_id, $location);
            } else {
                $lm->updateById($group_id, $location);
            }
        }
    }

    public function addGroup($data)
    {
        $lm = $this->getLocationModel();

        $group = array();
        $location = array();
        foreach ($data as $field => $value) {
            if ($this->fieldExists($field) && $field !== 'id') {
                $group[$field] = $value;
            } elseif ($lm->fieldExists($field) && $field !== 'group_id') {
                $location[$field] = $value;
            }
        }
        if (isset($data['location']) && is_array($data['location'])) {
            foreach ($data['location'] as $field => $value) {
                if ($lm->fieldExists($field) && $field !== 'group_id') {
                    $location[$field] = $value;
                }
            }
        }

        if (!$group) {
            return false;
        }

        $group_id = $this->insert($group);
        if ($location && $group['type'] == self::WA_GROUP_TYPE_LOCATION) {
            $lm->add($group_id, $location);
        }

        return $group_id;
    }

    public function deleteGroup($group_id)
    {
        $this->getLocationModel()->deleteById($group_id);
        $this->delete($group_id);
    }

    public function getEmptyRecord($filled = array())
    {
        $record = array_merge(
            $this->getEmptyRow(),
            array(
                'id' => null,
                'name' => null,
                'cnt' => 0,
                'icon' => null,
                'sort' => 0,
                'description' => null,
            ),
            (array) $filled
        );
        $record['location'] = $this->getLocationModel()->getEmptyRow();
        return $record;
    }


    /**
     * @return teamLocationModel
     */
    protected function getLocationModel()
    {
        if (!self::$tlm) {
            self::$tlm = new teamLocationModel();
        }
        return self::$tlm;
    }
}
