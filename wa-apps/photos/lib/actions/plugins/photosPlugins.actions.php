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
        $ui = wa()->whichUI();

        if ($ui === '1.3') {
            $sidebar_width = $this->getConfig()->getSidebarWidth();
            echo '<div class="content left'.$sidebar_width.'px">';
            parent::defaultAction();
            echo '</div>';
        }else{
            parent::defaultAction();
        }
    }
}