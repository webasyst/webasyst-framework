<?php

class waApiAuthConfig extends waBackendAuthConfig
{
    /**
     * @var waApiAuthConfig
     */
    protected static $instance = null;

    /**
     * @return waApiAuthConfig
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->ensureChannelExists();
        }
        return self::$instance;
    }
}
