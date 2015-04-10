<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage controller
 */
abstract class waViewController extends waController
{
    /**
     * @var waLayout
     */
    protected $layout = null;

    protected $blocks = array();

    public function __construct()
    {

    }

    /**
     * Set layout
     *
     * @param waLayout $layout
     */
    public function setLayout(waLayout $layout=null)
    {
        $this->layout = $layout;
    }

    /**
     * Execute controller and display result
     *
     * @see waController::run()
     */
    public function run($params = null)
    {
        parent::run($params);
        $this->display();
    }

    protected function preExecute()
    {
        wa()->getUser()->updateLastPage();
    }

    public function execute()
    {

    }

    /**
     * Return layout or null
     *
     * @return waLayout|null
     */
    public function getLayout()
    {
        if ($this->layout instanceof waLayout) {
            return $this->layout;
        }
        return null;
    }

    /**
     * Execute action $action and save result to array $this->blocks
     *
     * @param waViewAction $action
     * @param waDecorator $decorator
     * @param string $name
     */
    public function executeAction(waViewAction $action, $name = 'content', waDecorator $decorator = null)
    {
        $action->setController($this);
        if ($action->getLayout()) {
            $this->setLayout($action->getLayout());
        }
        $content = $decorator ? $decorator->display($action) : $action->display();
        if (isset($this->blocks[$name])) {
            $this->blocks[$name] .= $content;
        } else {
            $this->blocks[$name] = $content;
        }

    }

    /**
     * Display result
     */
    public function display()
    {
        if ($this->layout instanceof waLayout) {
            foreach ($this->blocks as $name => $content) {
                $this->layout->setBlock($name, $content);
            }
            $this->layout->display();
        } else {
            if ((wa()->getEnv() == 'frontend') && waRequest::param('theme_mobile') &&
                (waRequest::param('theme') != waRequest::param('theme_mobile'))) {
                wa()->getResponse()->addHeader('Vary', 'User-Agent');
            }
            // Send headers
            waSystem::getInstance()->getResponse()->sendHeaders();
            // Print all blocks
            foreach ($this->blocks as $content) {
                echo $content;
            }
        }
    }

}

// EOF