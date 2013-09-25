<?php

class photosPublicgalleryPluginBackendModerationController extends waJsonController
{    
    public function execute()
    {
        if (!$this->getUser()->getRights('photos', 'edit')) {
            throw new waException(_w("Access denied"));
        }
        $moderation = waRequest::post('moderation', '', waRequest::TYPE_STRING_TRIM);
        $id = waRequest::post('id', '', waRequest::TYPE_INT);
        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getById($id);
        if (!$photo) {
            $this->errors[] = _wp('Unknown photo');
        }
        if ($moderation == 'approve') {
            $photo_model->updateById($id, array('moderation' => 1));
            $photo_model->updateAccess($id, 1, array(0));
        }
        if ($moderation == 'decline') {
            $photo_model->updateById($id, array('moderation' => -1));
            $photo_model->updateAccess($id, 0, array(0));
        }
        
        $this->response['photo'] = $photo_model->getById($id);
        
        // update for making inline-editable widget
        $this->response['frontend_link_template'] = photosFrontendPhoto::getLink(array(
            'url' => '%url%'
        ));
        
        $this->response['counters'] = array(
            'declined' => $photo_model->countByField('moderation', -1),
            'awaiting' => $photo_model->countByField('moderation',   0)
        );
        
        // l18n string
        $count = (int) waRequest::post('count');
        $total_count = (int) waRequest::post('total_count');
        $this->response['string'] = array(
            'loaded' => _w('%d photo','%d photos', $count),
            'of' => sprintf(_w('of %d'), $total_count),
            'chunk' => ($count < $total_count) ? _w('%d photo','%d photos', min($this->getConfig()->getOption('photos_per_page'), $count - $total_count)) : false,
        );
        
    }    
}