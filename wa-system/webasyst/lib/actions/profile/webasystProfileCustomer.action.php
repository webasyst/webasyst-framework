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
        
        $auth_url = $result['details']['auth_url'];
        $goal_url = waRequest::get('goal_url', null, waRequest::TYPE_STRING_TRIM);
        if (!empty($goal_url)) {
            $url_parts = parse_url($auth_url);
            $auth_url = $url_parts['scheme'] . '://' . $url_parts['host'] . ifset($url_parts['path'], '/') 
                . '?goal_url=' . urlencode($goal_url) . '&' . ifset($url_parts['query'], '');
        }

        $this->redirect($auth_url);
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
