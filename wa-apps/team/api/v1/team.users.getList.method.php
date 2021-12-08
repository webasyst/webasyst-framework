<?php

class teamUsersGetListMethod extends waAPIMethod
{
    const ACCESS_LEVEL_LIMITED = 'limited';
    const ACCESS_LEVEL_FULL = 'full';

    public function execute()
    {
        $userList = $this->getList([
            'filter' => $this->getFilter()
        ]);

        // Convert "id => user" map to pure user array
        $result = [];
        foreach ($userList as $contact) {
            $result[] = $contact;
        }
        $this->response = $result;
    }

    /**
     * @return array $filter
     *      int[]       $filter['group_id'] [optional]
     *      string []   $filter['access'] [optional] - app_id => minimal level to access
     * @throws waException
     */
    protected function getFilter()
    {
        $filter = wa()->getRequest()->get('filter', [], waRequest::TYPE_ARRAY);
        $filter = is_array($filter) ? $filter : [];

        $group_ids = isset($filter['group_id']) ? waUtils::toIntArray($filter['group_id']) : [];
        $group_ids = waUtils::dropNotPositive($group_ids);
        if ($group_ids) {
            $filter['group_id'] = $group_ids;
        } else {
            unset($filter['group_id']);
        }

        $access = $this->formatAccessFilter($filter);
        if ($access) {
            $filter['access'] = $access;
        } else {
            unset($filter['access']);
        }

        return $filter;
    }

    /**
     * @param array $filter
     *      array|string $filter['access']
     *
     *      Examples
     *          $filter[access][crm]=limited - means >= limited
     *
     *          $filter[access][files]=full - means >= full
     *
     *          $filter[access]=crm - means same as filter[access][crm]=limited (>= limited)
     *
     *          $filter[access][]=crm   - means same as filter[access][crm]=limited (>= limited)
     *          $filter[access][]=files - means same as filter[access][files]=limited (>= limited)
     *
     *
     * @return array
     */
    protected function formatAccessFilter(array $filter)
    {
        if (!isset($filter['access'])) {
            return [];
        }

        $access_levels = array_fill_keys($this->getAccessLevels(), true);

        $access = [];

        // case of filter[access]=crm or filter[access][]=crm
        if (!is_array($filter['access']) || $this->isList($filter['access'])) {
            foreach (waUtils::toStrArray($filter['access']) as $app) {
                if ($app !== '') {
                    $access[$app] = self::ACCESS_LEVEL_LIMITED;
                }
            }
            return $access;
        }

        foreach ($filter['access'] as $app_id => $level) {
            if (isset($access_levels[$level])) {
                $access[$app_id] = $level;
            }
        }

        return $access;
    }

    protected function getFields()
    {
        return join(',', [
            'id',
            'name',
            'firstname',
            'lastname',
            'middlename',
            'company',
            'login',
            'email',
            'phone',
            'locale',
            'jobtitle',
            'last_datetime',
            'photo_url_16',
            'photo_url_32',
            'photo_url_96',
            'photo_url_144',
            '_event',
            'birth_day',
            'birth_month',
            'create_datetime',
            '_online_status'
        ]);
    }

    protected function getList(array $params = [])
    {
        $filter = isset($params['filter']) && is_array($params['filter']) ? $params['filter'] : [];

        $hash = $this->buildHash($filter);

        return $this->getUserList($hash, [
            'fields' => $this->getFields(),
            'convert_to_utc' => 'create_datetime',
            'filter' => $filter
        ]);
    }

    protected function workup(array &$list = [])
    {
        foreach ($list as &$contact) {
            $contact['userpic'] = $this->getDataResourceUrl(waContact::getPhotoUrl($contact['id'], $contact['photo'], 144));
            $contact['userpic_original_crop'] = $this->getDataResourceUrl(waContact::getPhotoUrl($contact['id'], $contact['photo'], 'original_crop'));
            $contact['userpic_uploaded'] = boolval($contact['photo']);

            $contact['userpic_thumbs'] = [];
            foreach ($contact as $field => $value) {
                if (substr($field, 0, 10) === 'photo_url_') {
                    $size = substr($field, 10);
                    $photo_url = $this->getDataResourceUrl(waContact::getPhotoUrl($contact['id'], $contact['photo'], $size));
                    $contact['userpic_thumbs'][$size] = $photo_url;
                }
            }
        }
        unset($contact);

        $this->extendByGroups($list);

        $this->unsetFields($list);
    }

    protected function extendByGroups(array &$list = [])
    {
        $ids = waUtils::getFieldValues($list, 'id');
        if (!$ids) {
            return;
        }

        $groups = (new waUserGroupsModel())->getGroupIdsForUsers($ids);
        foreach ($list as &$contact) {
            $contact['group_id'] = (array)ifset($groups, $contact['id'], []);
        }
        unset($contact);
    }

    protected function unsetFields(array &$list = [])
    {
        foreach ($list as &$contact) {
            unset($contact['is_company'], $contact['photo']);

            foreach ($contact as $field => $value) {
                if (substr($field, 0, 10) === 'photo_url_') {
                    unset($contact[$field]);
                }
            }
        }

        unset($contact);

    }

    protected function getDataResourceUrl($relative_url)
    {
        $cdn = wa()->getCdn($relative_url);
        if ($cdn->count() > 0) {
            return (string)$cdn;
        }
        $root_url = wa()->getRootUrl(true, false);
        return rtrim($root_url, '/') . '/' . ltrim($relative_url, '/');
    }

    protected function getUserList($hash, array $options)
    {
        $list = teamUser::getList($hash, $options);

        if (!empty($options['filter']['access'])) {
            $this->filterByAccess($list, $options['filter']['access']);
        }

        $this->workup($list);
        return $list;
    }

    protected function filterByAccess(array &$list, $access = [])
    {
        if (!$access) {
            return;
        }

        $contact_ids = waUtils::getFieldValues($list, 'id');
        $app_rights = $this->getAppRights($contact_ids, array_keys($access));

        foreach ($list as $idx => $user) {
            $id = $user['id'];

            // if user has not access to any app then drop it from list
            foreach ($access as $app_id => $level) {
                $right = isset($app_rights[$app_id][$id]) ? $app_rights[$app_id][$id] : 0;

                $has_access = ($level === self::ACCESS_LEVEL_LIMITED && $right >= 1) ||
                                ($level === self::ACCESS_LEVEL_FULL && $right > 1);

                if (!$has_access) {
                    unset($list[$idx]);
                    break;
                }
            }
        }
    }

    protected function getAppRights(array $contact_ids, array $apps)
    {
        $crm = new waContactRightsModel();

        $app_rights = [];
        foreach ($apps as $app_id) {
            $app_rights[$app_id] = $crm->getByIds($contact_ids, $app_id);
        }

        return $app_rights;
    }

    protected function buildHash(array $filter)
    {
        if (!empty($filter['group_id'])) {
            $filter['group_id'] = waUtils::toIntArray($filter['group_id']);
            $filter['group_id'] = waUtils::dropNotPositive($filter['group_id']);
            if ($filter['group_id']) {
                return "group/" . join(',', $filter['group_id']);
            }
        }
        return "users";
    }

    protected function getAccessLevels()
    {
        return [
            self::ACCESS_LEVEL_FULL, self::ACCESS_LEVEL_LIMITED
        ];
    }

    protected function isList(array $array)
    {
        return empty($array) || isset($array[0]);
    }
}
