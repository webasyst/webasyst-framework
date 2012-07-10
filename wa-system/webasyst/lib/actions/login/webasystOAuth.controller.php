<?php 

class webasystOAuthController extends waOAuthController
{
    protected function afterAuth($data)
    {
        parent::afterAuth($data);
        // display oauth template
        $this->executeAction(new webasysOAuthAction());
    }
}