<?php

class teamPluginsActions extends waPluginsActions
{
    protected $plugins_hash = '#';
    protected $is_ajax = false;
    protected $shadowed = true;

    public function defaultAction()
    {
        if (!teamHelper::hasRights()) {
            throw new waRightsException();
        }

        if (!teamHelper::isAjax()) {
            $this->setLayout(new teamDefaultLayout());
        }
        $this->getResponse()->setTitle(_w('Plugin settings page'));
        parent::defaultAction();
    }
}
