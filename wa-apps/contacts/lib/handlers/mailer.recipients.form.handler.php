<?php

class contactsMailerRecipientsFormHandler extends waEventHandler
{
    public function execute(&$params)
    {
        wa('contacts')->event('mailer.recipients.form', $params);  
    }
    
}