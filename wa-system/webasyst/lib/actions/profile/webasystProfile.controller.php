<?php
/**
 * Own profile editor for users who don't have access to Team app.
 * See also webasystProfilePageAction
 */
class webasystProfileController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new webasystProfileLayout());
        $this->executeAction(new webasystProfilePageAction());
    }
}

