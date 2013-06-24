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
 * @subpackage storage
 */
class waSessionStorage extends waStorage
{
    protected static $started = false;

    public function init($options = null)
    {
        $cookie_defaults = session_get_cookie_params();
        if (!isset($options['session_cookie_path']) && class_exists("waSystem")) {
            $options['session_cookie_path'] = waSystem::getInstance()->getRootUrl();
        }
        $options = array_merge(array(
            //'session_name'            => 'webasyst',
            'session_id'              => null,
            'auto_start'              => true,
            'session_cookie_lifetime' => $cookie_defaults['lifetime'],
            'session_cookie_path'     => $cookie_defaults['path'],
            'session_cookie_domain'   => $cookie_defaults['domain'],
            'session_cookie_secure'   => $cookie_defaults['secure'],
            'session_cookie_httponly' => true,
            'session_cache_limiter'   => 'none',
        ), $options);

        // initialize parent
        parent::init($options);

        if (isset($this->options['session_name'])) {
            session_name($this->options['session_name']);
        }

        if (!(bool)ini_get('session.use_cookies') && $session_id = $this->options['session_id'])    {
            session_id($session_id);
        }

        $lifetime = $this->options['session_cookie_lifetime'];
        $path     = $this->options['session_cookie_path'];
        $domain   = $this->options['session_cookie_domain'];
        $secure   = $this->options['session_cookie_secure'];
        $http_only = $this->options['session_cookie_httponly'];
        session_set_cookie_params($lifetime, $path, $domain, $secure, $http_only);

        if (null !== $this->options['session_cache_limiter']) {
            session_cache_limiter($this->options['session_cache_limiter']);
        }

        if ($this->options['auto_start']) {
            if (isset($_COOKIE[session_name()])) {
                $this->open();
            }
        }
    }

    public function open()
    {
        if (!self::$started) {
            session_start();
            self::$started = true;
        }
    }

    public function get($key)
    {
        return $this->read($key);
    }

    public function getAll()
    {
        return $_SESSION;
    }

    public function read($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return null;
    }

    public function del($key)
    {
        $this->remove($key);
    }

    public function remove($key)
    {
        if (!self::$started) {
            $this->open();
        }
        $data = null;
        if (isset($_SESSION[$key]))    {
            $data = $_SESSION[$key];
            unset($_SESSION[$key]);
        }
        return $data;
    }

    public function set($key, $data)
    {
        $this->write($key, $data);
    }

    /**
     * @param array|string $key
     * @param $data
     */
    public function write($key, $data)
    {
        if (!self::$started) {
            $this->open();
        }
        if (is_array($key) && count($key) == 2) {
            $_SESSION[$key[0]][$key[1]] = $data;
        } else {
            $_SESSION[$key] = $data;
        }
    }

    public function regenerate($destroy = false)
    {
        session_regenerate_id($destroy);
    }

    /**
     * Return true if before session was started and false otherwise
     *
     * @return bool
     */
    public function close()
    {
        $return = self::$started;
        self::$started = false;
        session_write_close();
        return $return;
    }

    public function destroy()
    {
        self::$started = false;
        session_unset();      
        session_destroy();
    }

    public function __destruct()
    {
         //$this->close();
    }
}
