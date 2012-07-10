<?php

class blogOAuthController extends waOAuthController
{
    public function afterAuth($data)
    {
        $params = $this->getStorage()->get('auth_params');
        if (isset($params['guest']) && $params['guest']) {
            $this->getStorage()->set('auth_user_data', $data);
        } else {
            parent::afterAuth($data);
        }

        wa('webasyst');
        $this->executeAction(new webasystOAuthAction());
    }
}
