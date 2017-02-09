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
class waDefaultViewController extends waViewController
{
    /**
     * @var waViewAction|string
     */
    protected $action;

    /**
     * @param waViewAction|string $action
     */
    public function setAction($action)
    {
        if ($action instanceof waViewAction) {
            $action->setController($this);
        }
        $this->action = $action;
    }

    public function getAction()
    {
        if (!$this->action instanceof waViewAction) {
            $class_name = $this->action;
            $this->action = new $class_name();
        }
        return $this->action;
    }

    public function execute()
    {
        if (!$this->layout && $this->getAction() && $this->getAction()->getLayout()) {
            $this->setLayout($this->getAction()->getLayout());
        }

        $this->executeAction($this->getAction());
    }
}
