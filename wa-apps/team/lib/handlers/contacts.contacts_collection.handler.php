<?php

class teamContactsContacts_collectionHandler extends waEventHandler
{
    public function execute(&$params)
    {
        return !!wa('team')->event('contacts_collection', $params);
    }
}
