<?php

class contactsContactsSaveGeocoordsController extends waJsonController
{
    public function execute()
    {
        
        // There is the same code in webasystProfileSaveGeocoords.controller.php
        
        $id = $this->getRequest()->request('id', null, waRequest::TYPE_INT);
        $sort = $this->getRequest()->request('sort', null, waRequest::TYPE_INT);
        if ($id && $sort !== null) {
            $lat = $this->getRequest()->request('lat', '', waRequest::TYPE_STRING);
            $lng = $this->getRequest()->request('lng', '', waRequest::TYPE_STRING);            
            
            $contact = new waContact($id);
            $address = array();
            foreach ($contact->get('address') as $i => $addr) {
                $address[$i] = array(
                    'value' => $addr['data'],
                    'ext' => $addr['ext']
                );
            }
            
            $address[$sort]['value']['lat'] = $lat;
            $address[$sort]['value']['lng'] = $lng;
            $contact->save(array(
                'address' => $address
            ));
        }
    }
}