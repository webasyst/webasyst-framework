<?php

class webasystTransportServiceApi extends waServicesApi
{
    /**
     * @param string $service
     * @param array $params
     * @param array &$api_result data fetched from API call
     * @return bool true if sufficient balance, false if insufficient balance
     * @throws waException if unable to connect to API
     */
    public function balanceCheckService($service, $params, &$api_result)
    {
        if (!isset($params['locale'])) {
            $params['locale'] = wa()->getLocale();
        }
        $api_result = $this->isBalanceEnough($service, ifset($params['count'], 1), $params['locale']);
        if ($api_result['status'] != 200) {
            throw new waException(_w('Webasyst API transport connection error.'));
        } else {
            return !empty($api_result['response']['is_enough']);
        }
    }
}
