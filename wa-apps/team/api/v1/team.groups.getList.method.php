<?php

class teamGroupsGetListMethod extends waAPIMethod
{
    public function execute()
    {
        $this->response = $this->getVisibleGroups([
            'filter' => $this->getFilter()
        ]);
    }

    protected function getGroups()
    {
        $m = new waGroupModel();
        $fields = array_keys($m->getMetadata());
        $fields = array_diff($fields, ['icon', 'sort']);
        return $m->select(join(",", $fields))->order('sort')->fetchAll('id');
    }

    /**
     * @return array $filter
     *      $filter['type'] [optional]
     * @throws waException
     */
    protected function getFilter()
    {
        $filter = wa()->getRequest()->get('filter', [], waRequest::TYPE_ARRAY);
        $filter = is_array($filter) ? $filter : [];

        $types = isset($filter['type']) ? waUtils::toStrArray($filter['type']) : [];
        if ($types) {
            $filter['type'] = $types;
        } else {
            unset($filter['type']);
        }

        return $filter;
    }

    public function getVisibleGroups(array $params = [])
    {
        $filter = isset($params['filter']) && is_array($params['filter']) ? $params['filter'] : [];

        $visible_groups = [];
        foreach ($this->getGroups() as $id => $g) {
            if (isset($filter['type']) && !in_array($g['type'], $filter['type'])) {
                continue;
            }
            if ($this->hasAccessToGroup($id)) {
                $visible_groups[] = $g;
            }
        }
        return $visible_groups;
    }

    protected function hasAccessToGroup($id)
    {
        return $this->getUser()->getRights('team', 'manage_users_in_group.'.$id) >= 0;
    }

    protected function getUser()
    {
        return wa()->getUser();
    }
}
