<?php
class teamCalendarSaveController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin($this->getAppId())) {
            throw new waRightsException();
        }
        $post_data = waRequest::post('data', null, waRequest::TYPE_ARRAY_TRIM);
        $required = array(
            'name' => 1, 'bg_color' => 0, 'font_color' => 0, 'default_status' => 0,
        );
        foreach ($required as $field => $not_null) {
            if (($not_null && empty($post_data[$field])) || (!$not_null && !isset($post_data[$field]))) {
                throw new waException('Field not found: '.$field);
            }
        }
        $ccm = new waContactCalendarsModel();

        $calendar_id = ifset($post_data['id']);
        if ($calendar_id) {
            $calendar = $ccm->getById($calendar_id);
            if (!$calendar) {
                throw new waException('Calendar not found');
            }
            $calendar['name'] = $post_data['name'];
            $calendar['bg_color'] = $post_data['bg_color'];
            $calendar['font_color'] = $post_data['font_color'];
            $calendar['is_limited'] = isset($post_data['is_limited']) && 1;
            $calendar['default_status'] = $post_data['default_status'];

            $ccm->updateById($calendar_id, $calendar);

            $this->logAction('calendar_edit', $calendar_id);

        } else {
            $calendar = array(
                'name' => $post_data['name'],
                'bg_color' => $post_data['bg_color'],
                'font_color' => $post_data['font_color'],
                'is_limited' => isset($post_data['is_limited']) && 1,
                'default_status' => $post_data['default_status'],
            );
            $calendar['id'] = $ccm->insert($calendar);

            $this->logAction('calendar_add', $calendar['id']);
        }
        $gm = new waGroupModel();
        $groups = $gm->select('*')->order('sort')->limit(50)->fetchAll('id');
        $right_model = new waContactRightsModel();
        foreach ($groups as $id => $g) {
            $rights = $right_model->get(-$g['id'], 'team', 'backend');
            if ($rights <= 1) {
                $right_model->save(
                    -$g['id'],
                    'team',
                    'edit_events_in_calendar.'.$calendar['id'],
                    empty($post_data['groups'][$id]) ? 0 : 1
                );
            }
        }
        $this->response = array(
            'calendar' => $calendar
        );
    }
}
