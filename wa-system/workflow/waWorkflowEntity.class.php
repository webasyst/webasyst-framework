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
 * Base class for waWorkflowAction and waWorkflowState
 */
class waWorkflowEntity
{
    /**
     * id as stored in database
     */
    public $id;

    /**
     * Human-readable name for debugging purposes or anything else subclasses may want to use it for.
     * Defaults to class name. May be changed in overriden init().
     * @var string
     */
    public $name;

    /**
     * @var array option => value
     */
    protected $options;

    /**
     * @var waWorkflow
     */
    protected $workflow;

    /**
     * @param string $id id as stored in database
     * @param waWorkflow $workflow
     * @param array $options option => value
     */
    public function __construct($id, waWorkflow $workflow, $options = array())
    {
        $this->id = $id;
        $this->workflow = $workflow;
        $this->options = array_merge($this->getDefaultOptions(), $options);
        $this->name = get_class($this);
        $this->init();
    }

    /**
     * Called by __construct() when $this-> vars are set up.
     */
    protected function init()
    {
        // override in subclasses...
    }

    /** Get option value by its name.
     * @param $opt
     * @return mixed
     */
    public function getOption($opt, $default=null)
    {
        if (isset($this->options[$opt])) {
            return $this->options[$opt];
        }
        return $default;
    }

    /**
     * Set value for an option
     * @param $opt
     * @param $value
     * @return \waWorkflowEntity
     */
    public function setOption($opt, $value)
    {
        $this->options[$opt] = $value;
        return $this;
    }

    /**
     * Get all options as array(key => value)
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
      * Used by a constructor to get default values for options.
      * Should be redefined in subclasses (must call parent::getDefaultOptions() and merge results)
      * @return array option => default value
      */
    public function getDefaultOptions()
    {
        return array();
    }

    /**
     * @return string|int id as stored in database
     */
    public function getId()
    {
        return $this->id;
    }

    /**
      * Human-readable name for debugging purposes or anything else subclasses may want to use it for.
      * Stored in $this->name (public anyway).
      * @return string
      */
    public function getName()
    {
        return $this->name;
    }

    /**
      * Workflow that this entity is attached to.
      * @return waWorkflow
      */
    public function getWorkflow()
    {
        return $this->workflow;
    }
}

