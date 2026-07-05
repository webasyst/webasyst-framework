<?php

class blogDesignActions extends waDesignActions
{

    protected $design_url = '#/design/';
    protected $themes_url = '#/design/themes/';

    public function __construct()
    {
        if (!$this->getRights('design')) {
            throw new waRightsException(_ws("Access denied"));
        }

        if (wa('blog')->whichUI() !== '1.3') {
            $this->options['is_ajax'] = true;
        }
        $this->options['js']['storage'] = false;
    }

    public function defaultAction()
    {
        $this->setLayout(new blogDefaultLayout());
        $this->getResponse()->setTitle(_ws('Design'));

        parent::defaultAction();
    }
}
