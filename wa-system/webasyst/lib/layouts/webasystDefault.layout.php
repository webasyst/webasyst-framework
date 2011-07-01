<?php

class webasystDefaultLayout extends waLayout
{
    public function __construct() 
    {
    	$this->view = waSystem::getInstance('webasyst')->getView();	
    }
	
}