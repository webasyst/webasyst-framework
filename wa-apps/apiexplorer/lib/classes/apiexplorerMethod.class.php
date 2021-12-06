<?php

/**
 * Class apiexplorerMethod
 *
 * @property string $name
 * @property string $short_name
 * @property string $class
 * @property string $type
 * @property string $doc
 */
class apiexplorerMethod extends apiexplorerObject
{
    public $app;
    public $type;

    protected $file_info;
    protected $name;
    protected $short_name;
    protected $class;
    protected $doc;
    /**
     * @var ReflectionClass
     */
    protected $reflection;

    /**
     * @var DocBlock
     */
    protected $doc_info;

    /**
     * @return mixed
     */
    public function getShortName()
    {
        if ($this->short_name === null) {
            $this->short_name = str_replace($this->app . '.', '', $this->getName());
        }

        return $this->short_name;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        if ($this->class === null) {
            $this->class = lcfirst(implode('',
                    array_map('ucfirst', explode('.', $this->getName())))) . 'Method';
        }

        return $this->class;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if ($this->name === null) {
            $this->name = str_replace('.method', '', $this->file_info->getBasename('.php'));
        }

        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDoc()
    {
        if ($this->doc === null && $this->getReflection() && $this->getReflection()->getDocComment()) {
            $this->doc_info = new DocBlock($this->getReflection()->getDocComment());
            $this->doc['params'] = isset($this->doc_info->all_params['param']) && is_array($this->doc_info->all_params['param'])
                ? $this->doc_info->all_params['param']
                : [];
            $this->doc['example'] = isset($this->doc_info->all_params['example']) && is_array($this->doc_info->all_params['example'])
                ? $this->doc_info->all_params['example']
                : [];
            $this->doc['return'] = trim($this->doc_info->return);
            $this->doc['summary'] = trim($this->doc_info->description);
            $this->doc['throws'] = isset($this->doc_info->all_params['throws']) && is_array($this->doc_info->all_params['throws'])
                ? $this->doc_info->all_params['throws']
                : [];
        }

        return $this->doc;
    }

    /**
     * apiexplorerMethod constructor.
     *
     * @param $file_info SplFileInfo
     */
    public function __construct($app, $file_info)
    {
        $this->app = $app;
        $this->file_info = $file_info;
        waAutoload::getInstance()->add('DocBlock', 'wa-apps/apiexplorer/lib/vendors/DocBlock.php');

    }

    /**
     * @return ReflectionClass
     */
    protected function getReflection()
    {
        if ($this->reflection === null) {
            try {
                $this->reflection = new ReflectionClass($this->getClass());
            } catch(Exception $ex) {}
        }
        
        return $this->reflection;
    }

    public function getType()
    {
        if ($this->type === null) {
            $props = $this->getReflection()->getDefaultProperties();
            $this->type = isset($props['method']) ? $props['method'] : 'GET';
        }

        return $this->type;
    }

    public function isRealMethod()
    {
        return $this->getReflection() !== null;
    }
}
