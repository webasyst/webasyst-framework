<?php
class teamContactsDeleteHandler extends waEventHandler
{
    public function execute(&$params)
    {
        wa('team')->event('contacts_delete', $params);
    }
}
