<?php

class teamCalendarExternalAuthorizeController extends waJsonController
{
    public function execute()
    {
        if ($this->isBegin()) {
            try {
                $this->authorizeBegin();
            } catch (teamCalendarExternalAuthorizeFailedException $e) {
                $this->errors[] = array('title' => _w('Authorize failed'), 'msg' => $e->getMessage());
                return;
            }
        } else {
            try {
                $this->authorizeEnd();
            } catch (teamCalendarExternalAuthorizeFailedException $e) {
                $params = $e->getParams();
                if (!empty($params['id'])) {
                    $this->getStorage()->set('team/calendar_external/' . $params['id'] . '/auth_failed', $e->getMessage());
                    $this->redirectToEdit($params['id']);
                } else {
                    die($e->getMessage());
                }
            }
        }
    }

    public function isBegin()
    {
        return !wa()->getRequest()->get('authorize_end') && !wa()->getRequest()->param('authorize_end');
    }

    public function getCalendarById($id)
    {
        $cem = new teamCalendarExternalModel();
        $calendar = $cem->getById($id);
        if (!$calendar) {
            throw new waException(_w('External calendar not found'));
        }
        if ($calendar['contact_id'] != wa()->getUser()->getId()) {
            throw new waRightsException(_w('Access denied'));
        }
        return $calendar;
    }

    public function authorizeBegin()
    {
        $calendar = $this->getCalendarById(wa()->getRequest()->get('id'));
        $plugin = teamCalendarExternalPlugin::factory($calendar['type'], true);
        if (!$plugin) {
            throw new waException(_w('External calendar plugin not found'));
        }
        die($plugin->authorizeBegin($calendar['id']));
    }

    public function authorizeEnd()
    {
        $id = wa()->getRequest()->get('id');
        if (!$id) {
            $id = $this->getRequest()->param('id');
        }
        $plugin = teamCalendarExternalPlugin::factory($id, true);
        if (!$plugin) {
            throw new waException(_w('External calendar plugin not found'));
        }
        $res = $plugin->authorizeEnd();
        waSystem::popActivePlugin();

        $id = $res['id'];

        $inner_calendar_id = (int) ifset($res['calendar_id']);
        $native_calendar_id = (string) ifset($res['native_calendar_id']);
        $name = (string) ifset($res['name']);

        $update = array();
        if ($inner_calendar_id > 0) {
            $update['calendar_id'] = $inner_calendar_id;
        }
        if (strlen($native_calendar_id) > 0) {
            $update['native_calendar_id'] = $native_calendar_id;
        }
        if (strlen($name) > 0) {
            $update['name'] = $name;
        }
        if ($update) {
            $cem = new teamCalendarExternalModel();
            $cem->updateById($id, $update);
        }

        $calendar = $this->getCalendarById($id);

        unset($res['id'], $res['calendar_id'], $res['native_calendar_id']);

        $params = $res;

        $cepm = new teamCalendarExternalParamsModel();
        $cepm->add($calendar['id'], $params);

        $this->redirectToEdit($calendar['id']);
    }

    public function redirectToEdit($id)
    {
        $url = wa('team')->getUrl(true) . "calendar/external/?id={$id}/";
        die("<script>window.location.href = '{$url}';</script>");
    }
}
