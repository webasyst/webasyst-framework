<?php
/** save contact data that came from contact add or contact edit form. */
class teamProfileSaveController extends webasystProfileSaveController
{
    protected $can_edit = null;

    protected function getContact()
    {
        $this->id = waRequest::post('id', null, 'int');
        $this->can_edit = teamUser::canEdit($this->id);
        if (!$this->id || !$this->can_edit) {
            $this->errors[] = [
                'id' => 'cannot_be_edited',
                'text' => _w('This profile is not available for editing.'),
            ];
            return null;
        }
        return new waContact($this->id);
    }

    protected function getData()
    {
        if ($this->can_edit === 'limited_own_profile') {
            return parent::getData();
        } else {
            $data = json_decode(waRequest::post('data', '[]', 'string'), true);
            if (!$data || !is_array($data)) {
                return null;
            }
            return $data;
        }
    }
}
