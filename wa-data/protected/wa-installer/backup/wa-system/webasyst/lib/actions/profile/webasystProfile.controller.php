<?php
/**
 * Own profile editor for users who don't have access to Contacts app.
 */
class webasystProfileController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new webasystProfileLayout());

        waSystem::getInstance('contacts', null, true);
        $this->executeAction(new contactsContactsInfoAction(array('limited_own_profile' => 1)));
    }
}

