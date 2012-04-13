<?php
class blogContactsLinksHandler extends waEventHandler
{
    /**
     * @param int $params Deleted contact_id
     * (non-PHPdoc)
     * @see waEventHandler::execute()
     */
    public function execute($params)
    {
        $post_model = new blogPostModel();
        $links = array();
        foreach ($params as $contact_id) {
            $links[$contact_id] = array();
            if ($count = $post_model->countByField('contact_id',$contact_id)) {
                $links[$contact_id][] = array(_w('Posts'),$count);
            }
        }
        return $links;
    }
}