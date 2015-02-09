<?php

/** 
 * A list of localized strings to use in JS. 
 */
class mailerBackendLocController extends waViewController
{
    public function execute() 
    {
        $this->executeAction(new mailerBackendLocAction());
    }
    
    public function preExecute() 
    {
        // do not save this page as last visited
    }
}
