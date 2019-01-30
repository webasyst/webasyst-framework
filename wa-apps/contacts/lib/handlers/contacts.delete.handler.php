<?php
class contactsContactsDeleteHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $ids = (array) $params;
        $nids = array();
        foreach ($ids as $i) {
            $nids[] = -(int)$i;
        }

        $contact_rights_model = new contactsRightsModel();
        $contact_rights_model->deleteByField('group_id', $nids);
    }
}
