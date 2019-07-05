<?php

function wa_header()
{
    try {
        wa('webasyst');
        $header = new webasystBackendHeaderAction();
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
