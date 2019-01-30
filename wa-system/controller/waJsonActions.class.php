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
abstract class waJsonActions extends waController
{
    protected $action;
    protected $response = array();
    protected $errors = array();


    protected function preExecute()
    {

    }

    protected function execute($action)
    {
        $method = $action.'Action';
        if (method_exists($this, $method)) {
            $this->$method();
        }else{
            throw new waException(sprintf("Invalid action or missed method at %s for action %s",get_class($this),$action));
        }
    }

    protected function postExecute()
    {

    }

    public function run($params = null)
    {
        $action = $params;
        if (!$action) {
            $action = 'default';
        }
        $this->action = $action;
        $this->preExecute();
        $this->execute($this->action);
        $this->postExecute();

        if ($this->action == $action) {
            if (waRequest::isXMLHttpRequest()) {
                $this->getResponse()->addHeader('Content-type', 'application/json');
            }
            $this->getResponse()->sendHeaders();
            if (!$this->errors) {
                echo waUtils::jsonEncode(array('status' => 'ok', 'data' => $this->response));
            } else {
                echo waUtils::jsonEncode(array('status' => 'fail', 'errors' => $this->errors));
            }
        }

    }

}
