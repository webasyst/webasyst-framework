<?php
class blogActivity
{
    const STATE_NEW = 2;
    const STATE_ACTUAL = 1;
    const STATE_OBSOLETE = 0;

    /**
     *
     * Time to expire data
     * @var int
     */
    private static $expiry_time = 180;

    /**
     *
     * Cached storage data
     * @var array
     */
    private $data = array();

    /**
     *
     * Current timestamp
     * @var int
     */
    private $timestamp;


    /**
     *
     * Last activity timestamp
     * @var int
     */
    private $timestamp_activity;

    /**
     *
     * Singletone instance
     * @var blogActivity
     */
    private static $instance;

    private function __construct($time = null)
    {
        $this->data = wa()->getStorage()->get(wa()->getApp());
        if (!$this->data) {
            $this->data = array();
        }
        $this->timestamp = time();
        $this->timestamp_activity = strtotime(self::getUserActivity());

        foreach ($this->data as $path => &$timestamps) {
            $filterd_timestamps = array_filter($timestamps,array($this,'filter'));
            if(!count($filterd_timestamps)) {

                $id = max(array_keys($timestamps));
                if($this->timestamp_activity && (($this->timestamp_activity - $timestamps[$id]) < self::$expiry_time)) {
                    $filterd_timestamps[$id] = $timestamps[$id];
                }
            }
            $timestamps = $filterd_timestamps;
            unset($timestamps);
        }
    }

    private function filter($timestamp)
    {
        return ($this->timestamp - $timestamp) <= self::$expiry_time;
    }

    private function __clone()
    {
        ;
    }

    public function __destruct()
    {
        wa()->getStorage()->set(wa()->getApp(), $this->data);
        self::$instance = null;
    }

    /**
     *
     * Get object instance
     * @param int $time
     * @return blogActivity
     */
    public static function getInstance($time = null)
    {
        if (!is_object(self::$instance)) {
            self::$instance = new self($time);
        }
        return self::$instance;
    }

    /**
     *
     * Store last access time
     * @param string $path
     * @param int|array $id
     */
    public function set($path, $id = array())
    {
        if ($id) {
            if(!isset($this->data[$path])) {
                $this->data[$path] = array();
            }
            if(is_array($id)) {
                $this->data[$path][min($id)] = $this->timestamp;
                $this->data[$path][max($id)] = $this->timestamp;
            } else {
                $this->data[$path][$id] = $this->timestamp;
            }
        }
        return $this->data[$path];
    }

    /**
     *
     * Check is data expired
     * @param string $path
     * @param int $id
     * @return int state
     */
    public function isNew($path, $id = null,$expire = null)
    {
        if( ($expire === null) || ($expire === false) ) {
            $expire = self::$expiry_time;
        }
        $state = self::STATE_NEW;
        if ($interval = $this->get($path)) {
            if($id < $interval['min']) {
                $state = self::STATE_OBSOLETE;
            } elseif ($id == $interval['min']) {
                $state = ($this->timestamp - $interval['timestamp_min']) > $expire ? self::STATE_OBSOLETE : self::STATE_ACTUAL;
            } elseif ($id < $interval['max']) {
                if ($expire) {
                    if ($expire < self::$expiry_time) {
                        if (!empty($this->data[$path][$id])) {
                            $state = ($this->timestamp - $this->data[$path][$id]) > $expire ? self::STATE_OBSOLETE : self::STATE_ACTUAL;
                        } else {
                            $max_timestamp = $this->timestamp_activity;
                            foreach ($this->data[$path] as $interval_id => $timestamp) {
                                if ($interval_id < $id) {
                                    $max_timestamp = $timestamp;
                                } else {
                                    break;
                                }
                            }
                            $state = ($this->timestamp - $max_timestamp) > $expire ? self::STATE_OBSOLETE : self::STATE_ACTUAL;
                        }
                    } else {
                        $state = self::STATE_ACTUAL;
                    }
                } else {
                    $state = self::STATE_OBSOLETE;
                }
            } elseif ($id == $interval['max']) {
                $state = ($this->timestamp - $interval['timestamp_max']) > $expire ? self::STATE_OBSOLETE : self::STATE_ACTUAL;
            }
        }
        return $state;
    }

    /**
     *
     * Get minimal actual id for path
     * @param string $path
     * return array[string]int interval actual ids
     */
    public function get($path)
    {
        $interval = false;
        if(!empty($this->data[$path])) {
            $ids = array_keys($this->data[$path]);
            $interval = array('min'=>min($ids),'max'=>max($ids),);
            $interval['timestamp_min'] = $this->data[$path][$interval['min']];
            $interval['timestamp_max'] = $this->data[$path][$interval['max']];
        }
        return $interval;
    }

    public static function getUserActivity($id = null, $sidebar = true)
    {
        $storage = wa()->getStorage();
        $blog_session_datetime = $storage->read('blog_session_datetime');
        if (!$blog_session_datetime && ($id ||(is_null($id) && ($id = wa()->getUser()->getId())))) {
            $contact = new waContactSettingsModel();
            $result = $contact->get($id, 'blog');

            if (!$blog_session_datetime) {
                $blog_last_datetime = isset($result['blog_last_datetime'])?$result['blog_last_datetime']:false;
                $blog_session_datetime = $blog_last_datetime ? $blog_last_datetime : self::setUserActivity($id);
                $storage->write('blog_badge_datetime',$blog_session_datetime);
            }

            $storage->set('blog_session_datetime',$blog_session_datetime);
        } else {
            if ($blog_session_datetime) {
                self::setUserActivity($id, 0);
            } else {
                $storage->write('blog_session_datetime',$blog_session_datetime = self::setUserActivity($id));
            }
        }

        if (!$sidebar && ($t = $storage->get('blog_badge_datetime'))) {
            $blog_session_datetime = $t;
        }

        return $blog_session_datetime;
    }

    public static function setUserActivity($id = null, $force = true)
    {
        if (is_null($id)) {
            $id = wa()->getUser()->getId();
        }
        $t = null;
        $storage = wa()->getStorage();
        if ($id) {
            if (!$force && (!($blog_last_datetime = $storage->get('blog_last_datetime')) || (( time() - strtotime($blog_last_datetime))>120))) {
                $force = 1;
            }
            if ($force) {
                $t = date("Y-m-d H:i:s",time()+1);
                $contact = new waContactSettingsModel();
                $contact->set($id, 'blog', 'blog_last_datetime', $t);
                $storage->write('blog_last_datetime', $t);
                if ($force === true) {
                    $storage->write('blog_badge_datetime', $t);
                }
            } elseif ($force === false) {
                $storage->write('blog_badge_datetime', $s = date("Y-m-d H:i:s",time()+1));
            }
        } elseif ($force) {
            $storage->set('blog_session_datetime',$t = date("Y-m-d H:i:s",time()+1));
        }
        return $t;
    }
}
