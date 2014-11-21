<?php

class contactsContactsContacts_collectionHandler extends waEventHandler
{
    public function execute(&$params)
    {
        return wa('contacts')->event('contacts.contacts_collection', $params);
    }
}