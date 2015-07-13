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
abstract class waJsonController extends waController
{

    protected $response = array();
    protected $errors = array();

    public function execute()
    {

    }
    
    public function run($params = null)
    {
        parent::run($params);
        $this->display();
    }

    public function display()
    {
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        $this->getResponse()->sendHeaders();
        if (!$this->errors) {
            $data = array('status' => 'ok', 'data' => $this->response);
            echo json_encode($data);
        } else {
            echo json_encode(array('status' => 'fail', 'errors' => $this->errors));
        }
    }

    public function getError()
    {

    }

    public function setError($message, $data = array())
    {
        $this->errors[] = array($message, $data);
    }
}