<?php 

abstract class waAbstractCaptcha
{
    protected $options = array();
    protected $required = array();
    
    public function __construct($options = array())
    {
        $this->options = $options + $this->options;
        foreach ($this->required as $k) {
            if (!isset($this->options[$k])) {
                throw new waException('Option '.$k.' is required');
            }
        }
    }
    
    abstract public function isValid($code = null);
    
    abstract public function getHtml();
} 