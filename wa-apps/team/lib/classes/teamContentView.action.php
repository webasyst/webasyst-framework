<?php

abstract class teamContentViewAction extends waViewAction
{
    /**
     * @return teamConfig
     */
    public function getConfig()
    {
        return parent::getConfig();
    }

    public function __construct($params = null)
    {
        parent::__construct($params);
        if (!teamHelper::isAjax()) {
            $this->setLayout(new teamDefaultLayout());
        }
    }
}
