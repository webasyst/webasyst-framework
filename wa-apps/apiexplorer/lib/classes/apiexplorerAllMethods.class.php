<?php


class apiexplorerAllMethods
{
	private $user;

	public function __construct($user)
    {
        $this->user = $user;
    }

    public function getList($force_renew = false)
    {
    	$key = 'app_methods/' . $this->user->getId();
        $cache = new waVarExportCache($key, 3600);
        $methods = $cache->get();

        if (empty($methods) || $force_renew) {
            $methods = [];
            $apps = array_keys($this->user->getApps());
            foreach($apps as $app) {
                $app2api = new apiexplorerMethods($app);
                $app_methods = $app2api->getMethods();
                foreach($app_methods as $name => $method) {
                    $methods[$app][$name] = ['type' => $method->getType()];
                }
            }
            $cache->set($methods);
        }
        return $methods;
    }
}
