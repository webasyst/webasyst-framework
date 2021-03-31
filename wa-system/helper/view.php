<?php

/**
 * Show webasyst header
 * @param array $options
 *      array  $options['custom']               some custom data for injecting into webasyst header
 *      string $options['custom']['content']    html content that will be shown in header
 *      string $options['custom']['user']       html content that will be shown inside user aread
 *
 * @return string
 */
function wa_header(array $options = [])
{
    try {
        wa('webasyst');
        // not inject all options, only that that supposed
        $params = waUtils::extractValuesByKeys($options, ['custom']);
        $header = new webasystBackendHeaderAction($params);
        return $header->display();
    } catch (waException $e) {
        return '';
    }
}

function wa_url($absolute = false)
{
    return waSystem::getInstance()->getRootUrl($absolute);
}

function wa_backend_url()
{
    return waSystem::getInstance()->getConfig()->getBackendUrl(true);
}
