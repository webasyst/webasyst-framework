<?php
/**
 * Saves to parent storage (defaults wa()->getStorage()), prepending a given prefix to all keys
 */
class waPrefixStorage extends waStorage
{
    public function init($options = array())
    {
        parent::init($options);
        if (empty($this->options['namespace']) || !is_string($this->options['namespace'])) {
            throw new waException('namespace is required');
        }
        if (empty($this->options['parent'])) {
            $this->options['parent'] = wa()->getStorage();
        }
    }

    public function read($key)
    {
        return $this->options['parent']->read($this->options['namespace'].'/'.$key);
    }

    public function regenerate($destroy = false)
    {
        // nothing to do
    }

    public function remove($key)
    {
        return $this->options['parent']->remove($this->options['namespace'].'/'.$key);
    }

    public function write($key, $data)
    {
        return $this->options['parent']->write($this->options['namespace'].'/'.$key, $data);
    }

    public function getAll()
    {
        if (!method_exists($this->options['parent'], 'getAll')) {
            throw new waException('Not supported by parent');
        }
        $result = array();
        $prefix = $this->options['namespace'].'/';
        $prefix_len = strlen($prefix);
        foreach($this->options['parent']->getAll() as $k => $v) {
            if (substr($k, 0, $prefix_len) === $prefix) {
                $result[substr($k, $prefix_len)] = $v;
            }
        }
        return $result;
    }
}
