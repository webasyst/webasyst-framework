<?php

class webasystDashboardUsersAction extends webasystDashboardViewAction
{
    public function execute()
    {
        $team = $this->getTeam();
        $users = $this->getTeamUsers($team);

        $contact_ids = $this->getContactIds($users);
        $activity = $this->getActivity($contact_ids);

        $this->view->assign(array_merge([
            'team' => $team,
            'users' => $users
        ], $activity));
    }

    protected function getContactIds(array $users)
    {
        $contact_ids = array_keys($users);
        $contact_ids = waUtils::toIntArray($contact_ids);
        return waUtils::dropNotPositive($contact_ids);
    }

    protected function getActivity(array $contact_ids)
    {
        if (!$contact_ids) {
            return [
                'activity' => [],
                'activity_load_more' => false
            ];
        }

        // activity stream
        $activity_action = new webasystDashboardActivityAction();

        $activity = $activity_action->getLogs(array('contact_id' => $contact_ids), $count);
        $activity_load_more = $count == 50;

        return [
            'activity' => $activity,
            'activity_load_more' => $activity_load_more
        ];
    }

    protected function getTeam()
    {
        $id = $this->getId();
        if ($id > 0) {
            $gm = new waGroupModel();
            $group = $gm->getById($id);
            if (!$group) {
                $this->notFound();
            }
            return $group;
        }
        return null;
    }

    protected function getId()
    {
        return waRequest::param('id');
    }

    protected function getTeamUsers($team)
    {
        $col = $this->getCollection($team);
        $col->orderBy('name', 'ASC');
        $users = $col->getContacts('*,photo_url_144', 0, $col->count());
        $this->workupUsers($users);
        return $users;
    }

    protected function workupUsers(&$users)
    {
        $team_exists = wa()->appExists('team');
        foreach ($users as &$user) {
            $user['name'] = waContactNameField::formatName($user);
            $user['link'] = $team_exists ? wa()->getAppUrl('team') . "u/{$user['login']}/info/" : '';
            $user['is_current_contact'] = $user['id'] == $this->getUserId();
        }
        unset($user);
    }

    protected function getCollection($team)
    {
        $options = ['photo_url_2x' => true];
        if ($team) {
            $col = new waContactsCollection('group/' . $team['id'], $options);
        } else {
            $col = new waContactsCollection('users', $options);
        }
        $col->addWhere('is_user=1');
        return $col;
    }
}
