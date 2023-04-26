<?php


class apiexplorerMethods
{
    public $app;
    /**
     * @var apitesterMethod[]
     */
    private $methods = [];
    protected $api;
    protected $path;

    /**
     * @return apitesterMethod[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    public function __construct($app = '', $api = 'v1')
    {
        $this->app = $app;
        $this->api = $api;
        if ($this->app && $this->api && wa()->appExists($this->app)) {
            wa($this->app);
            $this->readFiles();
        }
    }

    /**
     * @param $name string
     *
     * @return apitesterMethod
     * @throws apitesterNoMethodException
     */
    public function getMethod($name)
    {
        if (isset($this->methods[$name])) {
            return $this->methods[$name];
        }
        throw new apiexplorerNoMethodException('no method found with such name');
    }

    protected function readFiles()
    {
        $this->path = wa()->getAppPath('api/' . $this->api . '/', $this->app);
        $files = waFiles::listdir($this->path, true);
        foreach ($files as $file) {
            $method = new apiexplorerMethod($this->app, new SplFileInfo($file));
            if ($method->isRealMethod()) {
                $this->methods[preg_replace('/^(.*\/|)([^\/]+)\.method\.php$/', '$2', $file)] = $method;
            }
        }
        ksort($this->methods);
    }
}
