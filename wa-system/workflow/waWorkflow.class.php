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
 * @subpackage workflow
 */

/**
 * Class representing the workflow.
 */
class waWorkflow
{
    /**
     * @var array state_id => waWorkflowState
     */
    protected $states = array();

    /**
     * @var array action_id => waWorkflowAction
     */
    protected $actions = array();

    /**
     * @var string id of this workflow for application-specific uses. Not used by waWorkflow
     */
    public $id;

    /**
     * @var string workflow name for application-specific uses. Not used by waWorkflow
     */
    public $name;

    /**
     * Returns id of this workflow for application-specific uses. Not used by waWorkflow
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns workflow name for application-specific uses. Not used by waWorkflow
     * @return string
     */
    public function getName()
    {
        return waLocale::fromArray($this->name);
    }

    /**
     * Function to be overriden in subclasses.
     * Returns all possible states as an array(id => value).
     * Value can either be a class name or an array('classname' => ..., 'options' => array(...))
     * @return array
     */
    public function getAvailableStates()
    {
        return array(
            // 'state1' => 'Workflow1State1',
            // 'state2' => array('classname' => 'Workflow1State2', 'options' => array(...)),
        );
    }

    /**
     * Function to be overriden in subclasses.
     * Returns all possible actions as an array(id => value).
     * Value can either be a class name or an array('classname' => ..., 'options' => array(...))
     * @return array
     */
    public function getAvailableActions()
    {
        return array(
            // 'action1' => 'Workflow1Action1',
            // 'action2' => array('classname' => 'Workflow1Action2', 'options' => array(...)),
        );
    }

    /**
      * Default state for new requests and requests that lost their state for some emergency reason.
      * To be overriden in subclasses.
      * @param array $params implementation-specific
      * @return int state_id
      */
    public function getDefaultState($params = null)
    {
        return key($this->getAvailableStates());
    }

    /**
     * State instance by id.
     *
     * @param string|int $id
     * @throws waException
     * @return waWorkflowState
     */
    public function getStateById($id)
    {
        if (!isset($this->states[$id])) {
            $states = $this->getAvailableStates();
            if (isset($states[$id])) {
                $this->states[$id] = $this->createEntity($id, $states[$id]);
            } else {
                $default_state_id = $this->getDefaultState();
                if (!isset($states[$default_state_id])) {
                    throw new waException("Default state is not available");
                }
                return $this->getStateById($default_state_id);
            }
        }
        return $this->states[$id];
    }

    /**
     * Action instance by id.
     *
     * @param string|int $id
     * @return waWorkflowAction
     */
    public function getActionById($id)
    {
        if (!isset($this->actions[$id])) {
            $actions = $this->getAvailableActions();
            if (isset($actions[$id])) {
                $this->actions[$id] = $this->createEntity($id, $actions[$id]);
            } else {
                return null;
            }
        }
        return $this->actions[$id];
    }

    /**
     * @return array state_id => waWorkflowState
     */
    public function getAllStates()
    {
        foreach ($this->getAvailableStates() as $id => $data) {
            if (!isset($this->states[$id])) {
                $this->states[$id] = $this->createEntity($id, $data);
            }
        }
        return $this->states;
    }

    /**
     * @return array action_id => waWorkflowAction
     */
    public function getAllActions()
    {
        foreach ($this->getAvailableActions() as $id => $data) {
            if (!isset($this->actions[$id])) {
                $this->actions[$id] = $this->createEntity($id, $data);
            }
        }
        return $this->actions;
    }

    /**
     * Helper for getActionById(), getStateById(), getAllStates() and getAllActions().
     * Creates action or state object by key=>value from getAvailableStates() or getAvailableActions()
     *
     * @param mixed $id key from getAvailableStates()/getAvailableActions()
     * @param $data
     * @throws waException
     * @return waWorkflowEntity
     */
    protected function createEntity($id, $data)
    {
        if (is_array($data)) {
            $class_name = $data['classname'];
            $options = isset($data['options']) ? $data['options'] : array();
        } else {
            $class_name = $data;
            $options = array();
        }

        if (!class_exists($class_name)) {
            throw new waException('Workflow entity class not found: '.$class_name);
        }

        return new $class_name($id, $this, $options);
    }

    /**
     * Returns object of the action by class name.
     *
     * @param $class_name
     * @return waWorkflowAction
     * @deprecated
     */
    public function getActionByClass($class_name)
    {
        $actions = $this->getAvailableActions();
        $action_id = array_search($class_name, $actions); // !!! fails when item is an array

        return $this->getActionById($action_id);
    }

    /**
     * Returns object of the action by class name
     *
     * @param $class_name
     * @return waWorkflowState
     * @deprecated
     */
    public function getStateByClass($class_name)
    {
        $states = $this->getAvailableStates();
        $state_id = array_search($class_name, $states); // !!! fails when item is an array

        return $this->getStateById($state_id);
    }

    /**
     * Translate workflow relative path to application relative path.
     *
     * @param string $path relative path from workflow root
     * @return string relative path from application root
     */
    public function getPath($path = null)
    {
        return 'lib/workflow'.$path;
    }

    /**
     * Short for $this->getStateById($params['state_id'])
     *
     * @param mixed $params
     * @return waWorkflowState
     * @deprecated
     */
    public function getState($params = null)
    {
        if ($params) {
            return $this->getStateById($params['state_id']);
        }
    }

    /**
     * Short for $this->getStateById($params['state_id'])->getActions($params, $name_only)
     *
     * @param array $params
     * @param bool $name_only
     * @return array
     * @deprecated
     */
    public function getActions()
    {
        $args = func_get_args();
        $params = isset($args[0]) ? $args[0] : null;
        $name_only = isset($args[1]) ? $args[1] : false;
        $state = $this->getState($params);
        return $state->getActions($params, $name_only);
    }
}

