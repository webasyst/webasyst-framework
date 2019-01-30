<?php
class teamScheduleEventDeleteController extends waJsonController
{
    /**
     * @throws waException
     */
    public function execute()
    {
        $id = waRequest::post('id', null, waRequest::TYPE_INT);

        $cem = new teamWaContactEventsModel();
        $event = $cem->getById($id);

        if (!$event) {
            throw new waException('Event not found');
        }
        if ($event['contact_id'] != wa()->getUser()->getId() && !wa()->getUser()->isAdmin($this->getAppId())) {
            throw new waRightsException();
        }
        $result = $cem->deleteEvent($id);
        if ($result) {
            $this->logAction('event_delete', $id, $event['contact_id']);
        }

        $message = '';
        $external_events_result = (array) ifset($result['external_events_result']);
        foreach ($external_events_result as $event_id => $res) {
            if ($res === 'not_found') {
                $message = "Related external event does't exist";
                break;
            }
        }

        $this->response = array(
            'message' => $message
        );
    }
}
