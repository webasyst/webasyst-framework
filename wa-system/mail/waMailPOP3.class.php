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
 * @subpackage mail
 */
class waMailPOP3
{
    protected $options = array(
        'port' => 110
    );

    protected $server;
    protected $port;
    protected $login;
    protected $password;

    protected $handler;

    public function __construct($options)
    {
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
        }
        $this->server = ($this->getOption('ssl') ? 'ssl://' : '').$this->getOption('server');
        $this->port = $this->getOption('port');
        $this->user = $this->getOption('user');
        if (!$this->user) {
            $this->user = $this->getOption('login');
        }
        $this->password = $this->getOption('password');
         // try connect and auth to the mail server
        $this->connect();
    }

    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function connect()
    {
        // try open socket
        if ($this->getOption('tls')) {
            $this->handler = @stream_socket_client('tcp://'.$this->server.':'.$this->port, $errno, $errstr, $this->getOption('timeout', 10));
            if ($this->handler) {
                stream_socket_enable_crypto($this->handler, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }
        } else {
            $this->handler = @fsockopen($this->server, $this->port, $errno, $errstr, $this->getOption('timeout', 10));
        }
        if ($this->handler) {
            // read welcome
            $this->read();
            // auth
            $this->auth();
        } else {
            if (!preg_match('//u', $errstr)) {
                $tmp = @iconv('windows-1251', 'utf-8//ignore', $errstr);
                if ($tmp) {
                    $errstr = $tmp;
                }
            }
            throw new waException($errstr.' ('.$errno.')', $errno);
        }
    }

    protected function auth()
    {
        $this->exec("USER ".$this->user);
        $this->exec("PASS ".$this->password);
        return true;
    }


    public function exec($command, $read = true)
    {
        if (false === fwrite($this->handler, $command."\r\n")) {
            throw new waException("Cannot write to ".$this->server);
        }
        if (!$read) {
            return "";
        }
        $data = $this->read();
        if (stripos($data, '+OK') === 0) {
            return trim(substr($data, 3));
        } elseif (stripos($data, '-ERR') === 0){
            throw new waException('Error from '.$this->server.': ' . trim(substr($data, 4)));
        } else {
            throw new waException('Unknown response from '.$this->server.' ('.$command.'): '.$data);
        }
    }

    public function read($length = false)
    {
        if ($length) {
            $data = fread($this->handler, $length);
        } else {
            $data = fgets($this->handler);
        }
        return $data;
    }

    /**
     * Return the number of messages in the box and the total size of those messages in bytes
     *
     * @return array($number, $size)
     */
    public function count()
    {
        $data = $this->exec("STAT");
        if (strpos($data, ' ') !== false) {
            return explode(" ", $data);
        } else {
            return array(0, 0);
        }
    }

    public function getIds()
    {
        $this->exec("UIDL");
        $result = array();
        while(rtrim($data = $this->read()) != '.') {
            if (stripos($data, '+OK') === 0) {
                continue;
            }
            $result[strtok($data, ' ')] = strtok('');
        }
        return $result;
    }

    public function get($id, $file = false)
    {
        $this->exec("RETR ".$id);
        if ($file) {
            $fh = @fopen($file, "w+");
            if (!$fh) {
                throw new waException("Cannot open file ".$file);
            }
        } else {
            $result = '';
        }
        while(rtrim($data = $this->read()) != '.') {
            if ($file) {
                fwrite($fh, $data);
            } else {
                $result .= $data;
            }
        }
        if ($file) {
            fclose($fh);
            return $file;
        }
        return $result;
    }

    /**
     * Delete message by id
     */
    public function delete($id)
    {
        return $this->exec("DELE ".$id);
    }

    /**
     * Close connection
     */
    public function close()
    {
        $this->exec("QUIT", false);
        fclose($this->handler);
    }
}