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

class waWorkflowState extends waWorkflowEntity
{
    /**
     * Array of actions that can be performed from this state.
     * Uses $this->getAvailableActionIds() (or deprecated $this->getAvailableActions() as a fallback option)
     * as a source.
     *
     * @param array $params implementation-specific parameters
     * @param boolean $name_only true to return actions names, false (default) to return waWorkflowAction instances
     * @return array action_id => waWorkflowAction or string depending on $name_only
     */
    public function getActions($params = null)
    {
        $args = func_get_args();
        $name_only = isset($args[1]) ? $args[1] : false;
        $actions = array();
        $actions_ids = $this->getAvailableActionIds($params);
        if ($actions_ids === null) {
            // Use deprecated getAvailableActions() since getAvailableActionIds() returned null
            $actions_classes = $this->getAvailableActions($params);
            if ($actions_classes && is_array($actions_classes)) {
                foreach ($actions_classes as $class) {
                    if ($action = $this->workflow->getActionByClass($class)) {
                        if (!$name_only) {
                            $actions[$action->getId()] = $action;
                        } else {
                            $actions[$action->getId()] = $action->getName();
                        }
                    }
                }
            }
        } else {
            // Use result of getAvailableActionIds()
            foreach ($actions_ids as $action_id) {
                if ($action = $this->workflow->getActionById($action_id)) {
                        if (!$name_only) {
                            $actions[$action->getId()] = $action;
                        } else {
                            $actions[$action->getId()] = $action->getName();
                        }
                }
            }
        }
        return $actions;
    }

    /**
     * Return list of ids of available actions in the state
     *
     * @param mixed $params
     * @return array
     */
    protected function getAvailableActionIds($params = null)
    {
        /**
         * return array(
         *        'action1_id',
         *        'action2_id'
         * );
         */
        return null;
    }

    /**
     * Return array of class names for available actions in the state.
     * Used instead of $this->getAvailableActionIds() as a fallback option in case it returns null.
     *
     * @param mixed $params
     * @return array
     * @deprecated
     */
    protected function getAvailableActions($params = null)
    {
        /**
         * return array(
         *        'Workflow1Action1',
         *        'Workflow1Action2'
         * );
         */
    }
}
