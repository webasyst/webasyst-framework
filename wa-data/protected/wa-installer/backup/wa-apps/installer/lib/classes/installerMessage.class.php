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
 * @package installer
 */

class installerMessage
{
    const R_SUCCESS = 'success';
    const R_FAIL = 'fail';
    private static $instance = null;
    private $storage = null;
    private $messages = array();

    /**
     *
     * @return installerMessage
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->storage = waSystem::getInstance()->getStorage();
        $this->messages = $this->storage->read(__CLASS__);
        if (!is_array($this->messages)) {
            $this->messages = array();
        }
    }

    private function __clone()
    {

    }

    private function store()
    {
        $overdue = array();
        foreach ($this->messages as $id => $message) {
            if (!is_array($message)) {
                $overdue[] = $id;
            } elseif (isset($message['t'])) {
                if ((time() - $message['t']) > 300) {
                    $overdue[] = $id;
                }
            }
        }
        foreach ($overdue as $id) {
            unset($this->messages[$id]);
        }
        $this->storage->write(__CLASS__, $this->messages);
    }

    /**
     *
     * @param $message_id string|array
     * @return array
     */
    public function handle($message_id)
    {
        $messages = array();
        foreach ((array)$message_id as $id) {
            if (isset($this->messages[$id])) {
                $messages[$id] = $this->messages[$id];
                unset($this->messages[$id]);
            }
        }
        $this->store();
        foreach ($messages as &$message) {
            $message['text'] = preg_replace_callback('/\[?`([^`]+)`\]?/', array($this, 'translate'), $message['text']);
            unset($message);
        }
        return $messages;
    }

    /**
     *
     * @param $message
     * @param $result
     * @return string
     */
    public function raiseMessage($message, $result = self::R_SUCCESS)
    {
        $ids = array_keys($this->messages);
        do {
            $id = rand(10000, 99999);
        } while (in_array($id, $ids));
        $this->messages[$id] = array(
            'text'   => $message,
            'result' => $result,
            't'      => time(),
        );
        $this->store();
        return $id;
    }

    private function translate($a)
    {
        return _w($a[1]);
    }
}
//EOF
