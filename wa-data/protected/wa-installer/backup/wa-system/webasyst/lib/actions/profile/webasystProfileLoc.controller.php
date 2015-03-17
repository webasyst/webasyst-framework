<?php
/** A list of localized strings to use in JS. */
class webasystProfileLocController extends waViewController
{
    public function execute()
    {
        waSystem::getInstance('contacts', null, true);
        $this->executeAction(new contactsBackendLocAction());
    }

    public function preExecute()
    {
        // do not save this page as last visited
    }
}

