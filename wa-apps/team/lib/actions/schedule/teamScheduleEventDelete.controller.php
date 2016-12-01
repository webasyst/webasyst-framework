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
        $cem->deleteEvent($id);

        $this->logAction('event_delete', $id, $event['contact_id']);
    }
}
