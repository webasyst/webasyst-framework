<?php

class photosPagesActions extends waPageActions
{

    public function __construct()
    {
        if (!$this->getRights('pages')) {
            throw new waRightsException("Access denied");
        }
        $this->options['is_ajax'] = true;
    }

    protected $url = '#/pages/';
    protected $add_url = '#/pages/add';
}