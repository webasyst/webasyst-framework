<?php

class contactsBackendController extends waViewController
{
	public function execute()
	{
		$this->setLayout(new contactsDefaultLayout());
	}
}