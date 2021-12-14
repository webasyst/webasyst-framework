<?php
/**
 * Timeline tab in profile.
 */
class teamProfileActivityAction extends waViewAction
{
    public function execute()
    {
        $min_id = waRequest::request('min_id', null, waRequest::TYPE_INT);
        $max_id = waRequest::request('max_id', null, waRequest::TYPE_INT);
        $user_id = waRequest::request('user_id', null, waRequest::TYPE_INT);
        if (!$user_id) {
            $user = teamUser::getCurrentProfileContact();
            $user_id = $user['id'];
        }
        $activity_action = new webasystDashboardActivityAction();
        $this->view->assign('activity', $activity_action->getLogs(array(
            'min_id' => $min_id,
            'max_id' => $max_id,
            'contact_id' => $user_id,
        ), $count));
        $this->view->assign('count', $count);
        $this->view->assign('user_id', $user_id);
    }
}
