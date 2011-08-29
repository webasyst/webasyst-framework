<?php 

abstract class waAuthAdapter
{
    
    protected $options = array();
    
	public function __construct($options = array())
	{
		if (is_array($options)) {
			foreach ($options as $k => $v) {
				$this->options[$k] = $v;
			}
		}
	}
	
	abstract public function auth();
    
    public function isAuth()
    {
        return waSystem::getInstance()->getStorage()->set('auth_user_data');
    }
    
	public function clearAuth()
	{
		waSystem::getInstance()->getStorage()->remove('auth_user_data');
	}        
	
	public function getName()
	{
	    $class = substr(get_class($this), 0, -4);
	    return ucfirst($class);
	}
}