<?php

class photosCommentCache
{
    /**
     * @var photosCommentCache
     */
    static private $instance;

    /**
     * @var waSerializeCache
     */
    private $cache = null;

    private function __construct()
    {
        $this->cache = new waSerializeCache('comment_plugin', 300, 'photos');
    }

    public function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new photosCommentCache();
        }
        return self::$instance;
    }

    public function get() {
        return $this->cache->get();
    }
    public function set($value) {
        return $this->cache->set($value);
    }
    public function delete() {
        return $this->cache->delete();
    }
    public function isCached() {
        return $this->cache->isCached();
    }

}