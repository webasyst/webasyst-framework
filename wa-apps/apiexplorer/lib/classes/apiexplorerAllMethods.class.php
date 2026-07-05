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
            $apps = ($this->user == null) ? [] : $this->user->getApps();
            unset($apps['stickies']);
            $apps = array_keys($apps);
            $apps[] = 'webasyst';
            foreach($apps as $app) {
                try {
                    $app2api = new apiexplorerMethods($app);
                    $app_methods = $app2api->getMethods();
                    foreach($app_methods as $name => $method) {
                        $methods[$app][$name] = ['type' => $method->getType()];
                    }
                } catch (waException $e) {
                    // ignore apps that fail to initialize
                }
            }
            $cache->set($methods);
        }
        return $methods;
    }
}
