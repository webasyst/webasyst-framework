<?php

/**
 * Provider healthy urls for services
 * If url not working you can complain about it and after some max tries urls will be marked as not healthy and provider suggests alternative urls
 */
class waServicesUrlsProvider extends waWebasystIDUrlsProvider
{
    public function getServiceUrl($service, $params = [])
    {
        $service_url = $this->tryGetServiceUrl($service, $params);
        if (empty($service_url)) {
            $this->config->keepEndpointsSynchronized(true);
            $service_url = $this->tryGetServiceUrl($service, $params);
        }
        
        return $service_url;
    }

    private function tryGetServiceUrl($service, $params = [])
    {
        $service_url = null;
        $api_url = $this->getApiEndpoint();
        foreach ($this->config->getEndpoints() as $endpoint) {
            if (!empty($endpoint['api']) && $endpoint['api'] === $api_url) {
                if (isset($endpoint['services']) && isset($endpoint['services'][$service])) {
                    $service_url = $endpoint['services'][$service];
                }
                break;
            }
        }
        if (!empty($service_url) && !empty($params)) {
            $service_url .= '?' . http_build_query($params);
        }
        return $service_url;
    }
}
