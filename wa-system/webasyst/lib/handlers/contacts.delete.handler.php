<?php

class webasystContactsDeleteHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $contact_ids = $params;
        waContact::clearWebasystIDAssets($contact_ids);
    }
}
