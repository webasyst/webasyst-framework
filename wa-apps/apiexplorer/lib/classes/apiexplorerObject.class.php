<?php

class apiexplorerObject
{
    protected function formatMethodName($name)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    public function __get($name)
    {
        $method = 'get' . $this->formatMethodName($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        throw new waException('no attribute');
    }

    public function __set($name, $value)
    {
        $method = 'set' . $this->formatMethodName($name);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
    }
}