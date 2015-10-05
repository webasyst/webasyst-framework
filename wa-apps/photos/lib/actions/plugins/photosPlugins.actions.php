<?php

class photosPluginsActions extends waPluginsActions
{
    protected $shadowed = true;

    public function preExecute()
    {
        if (!$this->getUser()->isAdmin('photos')) {
            throw new waRightsException(_ws('Access denied'));
        }
    }

    public function defaultAction()
    {
        $config = $this->getConfig();
        $sidebar_width = $config->getSidebarWidth();

        echo '<div class="content left'.$sidebar_width.'px">';
        parent::defaultAction();
        echo '</div>';
    }
}