<?php

class photosContactsLinksHandler extends waEventHandler
{
    /**
     * @param array $params deleted contact_id
     * @return array|void
     */
    public function execute(&$params)
    {
        waLocale::loadByDomain('photos');
        // TODO: take a look to other models related with contacts

        $links = array();
        
        $photo_model = new photosPhotoModel();
        foreach ($params as $contact_id) {
            $links[$contact_id] = array();
            if ($count = $photo_model->countByField('contact_id', $contact_id)) {
                $links[$contact_id][] = array(
                    'role' => _wd('photos', 'Photos author'),
                    'links_number' => $count,
                );
            }
        }
        
        $ext_links = wa()->event(array('photos', 'contacts_links'), $params);
        foreach ($params as $contact_id) {
            foreach ($ext_links as $plugin_name => $lnks) {
                if (is_array($lnks) && isset($lnks[$contact_id])) {
                    $links[$contact_id] = array_merge($links[$contact_id], $lnks[$contact_id]);
                }
            }
        }
        
        return $links;
    }
}

