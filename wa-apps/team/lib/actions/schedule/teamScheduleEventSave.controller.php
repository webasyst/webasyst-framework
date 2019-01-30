<?php
class teamScheduleEventSaveController extends waJsonController
{
    public function execute()
    {
        try {
            $this->executeController();
        } catch (teamCalendarExternalTokenInvalidException $e) {
            $params = $e->getParams();
            $external_calendar_id = (int) $params['external_calendar_id'];
            if ($external_calendar_id > 0) {
                $this->setError('redirect', wa('files')->getAppUrl() . 'calendar/external/?id=' . $external_calendar_id);
                return;
            }
            throw $e;
        } catch (waException $e) {
            throw $e;
        }
    }

    /**
     * @throws waException
     */
    public function executeController()
    {
        $post_data = waRequest::post('data', array(), waRequest::TYPE_ARRAY_TRIM);
        $id = intval(ifset($post_data['id']));

        $allowed = array(
            'calendar_id' => 1, 'summary' => 1, 'start' => 1, 'end' => 1,
            'is_allday' => 0, 'is_status' => 0, 'description' => 0, 'location' => 0, 'contact_id' => 1,
            'calendar_id' => 1, 'summary_type' => 0
        );
        foreach ($allowed as $field => $require_nonempty) {
            if (($require_nonempty && empty($post_data[$field])) || (!$require_nonempty && !isset($post_data[$field]))) {
                throw new waException('Field not found: '.$field);
            }
        }

        // Convert $post_data to $data, keeping only allowed fields
        $now = date('Y-m-d H:i:s');
        $data = array_intersect_key($post_data, $allowed);
        $data['update_datetime'] = $now;

        // Convert time according to contact timezone
        if (!$data['is_allday']) {
            $data['start'] = waDateTime::parse('Y-m-d H:i:s', date('Y-m-d H:i:s', strtotime($data['start'])));
            $data['end'] = waDateTime::parse('Y-m-d H:i:s', date('Y-m-d H:i:s', strtotime($data['end'])));
        }

        if (!$data['start'] || !$data['end'] || strtotime($data['start']) > strtotime($data['end'])) {
            throw new waException('Date(s) not found');
        }

        $cem = new teamWaContactEventsModel();
        if ($id) {
            $event = $cem->getEvent($id);
            if (!$event) {
                throw new waException('Event not found');
            }

            // Check both previous and new calendar
            teamHelper::checkCalendarRights($event['calendar_id'], $event['contact_id']);
            if ($event['calendar_id'] != $data['calendar_id']) {
                teamHelper::checkCalendarRights($data['calendar_id'], $event['contact_id']);
            }

            $update = $data;
            $update['sequence'] = $event['sequence'] + 1;
            $update['external_events'] = $event['external_events'];

            $result = $cem->updateEvent($id, $update);

            if ($result) {
                $this->logAction('event_edit', $id, $event['contact_id']);
            }

            $message = '';
            $external_events_result = (array) ifset($result['external_events_result']);
            foreach ($external_events_result as $event_id => $res) {
                if ($res === 'not_found') {
                    $message = "Related external event does't exist";
                    break;
                }
            }

            $this->response['message'] = $message;

        } else {

            $contact_id = intval(!empty($post_data['contact_id']) ? $post_data['contact_id'] : wa()->getUser()->getId());

            teamHelper::checkCalendarRights($data['calendar_id'], $contact_id);
            /*
            $c = new waContact($contact_id);
            if (!$c->getRights('team', 'manage_users_in_group.'.$id)) {
                throw new waRightsException();
            }
            */
            $id = $cem->addEvent($data + array(
                'contact_id' => $contact_id,
                'sequence' => 0,
            ));
            $this->logAction('event_add', $id, $contact_id);
        }
        $this->response['id'] = $id;
    }
}
