<?php

class waCacheModel extends waModel
{
    protected $table = 'wa_cache';

    public function add($name, $ttl)
    {
        if ($ttl === 0) {
            return true;
        }

        $sql = "INSERT INTO {$this->table}
                  SET name = :name,
                      expires = :expires
                  ON DUPLICATE KEY UPDATE expires = :expires";

        $datetime = new DateTime();
        $datetime->modify('+'.(int) $ttl.' sec');
        $expires_datetime = $datetime->format('Y-m-d H:i:s');

        $vars = array(
            'name'    => $name,
            'expires' => $expires_datetime,
        );

        return !!$this->query($sql, $vars);
    }

    public function getInvalid($params = array())
    {
        if (!empty($params['limit']) && wa_is_int($params['limit'])) {
            $limit = (int) ifset($params, 'limit', 20);
        } else {
            $limit = null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE expires <= ? LIMIT $limit";
        $date = date('Y-m-d H:i:s');
        $result = $this->query($sql, $date)->fetchAll('id');
        return $result;
    }

    public function deleteInvalid($params = array())
    {
        $invalid = $this->getInvalid($params);
        if (empty($invalid)) {
            return true;
        }

        foreach ($invalid as $cache) {
            $root_path = wa()->getConfig()->getRootPath();
            $cache_path = $root_path . $cache['name'];
            try {
                waFiles::delete($cache_path);
            } catch (waException $e) { }
        }

        $invalid_ids = array_keys($invalid);
        return $this->deleteById($invalid_ids);
    }
}