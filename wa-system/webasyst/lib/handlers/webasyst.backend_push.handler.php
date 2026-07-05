<?php

class webasystWebasystBackend_pushHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $current_app_info = ifempty($params, 'current_app_info', array());
        
        if (empty($current_app_info['id']) || $current_app_info['id'] != 'webasyst') {
            return false;
        }

        return true;
    }
}