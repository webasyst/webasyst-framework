<?php

class photosContactsDeleteHandler extends waEventHandler
{
    
    /**
     * @param int[] $params Deleted contact_id
     * @see waEventHandler::execute()
     * @return void
     */
    public function execute(&$params)
    {
        $contact_ids = $params;
        
        $photo_model = new photosPhotoModel();
        $photo_model->updateByField(array(
            'contact_id' => $contact_ids
        ), array(
            'contact_id' => 0
        ));
        
         wa()->event(array('photos', 'contacts_delete'), $params);
        
    }
}