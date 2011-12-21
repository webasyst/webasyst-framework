<?php

/** 
 * A list of localized strings to use in JS. 
 */
class siteBackendLocController extends waViewController
{
    public function execute() 
    {
        $this->executeAction(new siteBackendLocAction());
    }
    
    public function preExecute() 
    {
        // do not save this page as last visited
    }
}
