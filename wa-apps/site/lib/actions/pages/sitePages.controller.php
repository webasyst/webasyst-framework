<?php 

class sitePagesController extends waViewController
{
	public function execute()
	{
	    if (!waRequest::isXMLHttpRequest()) {
		    $this->setLayout(new siteDefaultLayout());
	    } else {
	        $this->executeAction(new sitePagesAction());
	    }
	}
}
