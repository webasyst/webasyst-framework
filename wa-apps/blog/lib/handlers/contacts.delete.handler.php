<?php

class blogContactsDeleteHandler extends waEventHandler
{
    /**
     * @param int[] $params Deleted contact_id
     * @see waEventHandler::execute()
     * @return void
     */
    public function execute(&$params)
    {
        $contact_model = new waContactModel();
        $contacts = $contact_model->getByField('id',$params,true);
        $post_model = new blogPostModel();
        $comment_model = new blogCommentModel();
        foreach ($contacts as $contact) {
            $data = array('contact_id'=>0,'contact_name'=>$contact['name']);
            $post_model->updateByField('contact_id',$contact['id'],$data);
            $data = array('contact_id'=>0,'name'=>$contact['name'],'auth_provider'=>null);
            $comment_model->updateByField('contact_id',$contact['id'],$data);
        }
        
        /**
         * @event contacts_delete
         * @param array[] int $contact_ids array of contact's ID
         * @return void
         */
        wa()->event(array('blog', 'contacts_delete'), $params);
    }
}