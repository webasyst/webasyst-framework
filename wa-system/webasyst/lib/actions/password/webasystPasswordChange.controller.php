<?php

class webasystPasswordChangeController extends waViewController
{
    public function execute()
    {
        $this->executeAction(new webasystPasswordChangeAction());
    }
}
