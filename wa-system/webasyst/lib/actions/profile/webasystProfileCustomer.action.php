<?php

/**
 * Class webasystProfileCustomerAction
 *
 * Authorization into customer center (customer portal), aka reverse authorization
 */
class webasystProfileCustomerAction extends waViewAction
{
    public function execute()
    {
        $result = $this->getAuthUrl();
        if (!$result['status']) {
            $this->view->assign([
                'status' => 'fail',
                'details' => $result['details']
            ]);
            return;
        }

        $this->redirect($result['details']['auth_url']);
    }


    /**
     * Get auth code for authorization into customer center (aka reverse authorization)
     * @return array - see waWebasystIDApi::getAuthUrl for format
     * @see waWebasystIDApi::getAuthUrl()
     * @throws waDbException
     * @throws waException
     */
    protected function getAuthUrl()
    {
        $api = new waWebasystIDApi();
        return $api->getAuthUrl(wa()->getUser()->getId());
    }
}
