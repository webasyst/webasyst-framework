<?php

class photosDesignActions extends waDesignActions
{
    protected $design_url = '#/design/';
    protected $themes_url = '#/design/themes/';


    public function __construct()
    {
        if (!$this->getRights('design')) {
            throw new waRightsException("Access denued");
        }
        $this->options['is_ajax'] = true;
        $this->options['js']['storage'] = false;
    }
}
