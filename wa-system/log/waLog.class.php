<?php

/** Ad hoc logging to /wa-log/log.txt */
class waLog
{
    public static function log($message) {
        $fd = fopen(waSystem::getInstance()->getConfig()->getPath('log').'/log.txt', 'a');
        if (!flock($fd, LOCK_EX)) {
            throw new waException('Unable to lock '.waSystem::getInstance()->getConfig()->get('root_path').'/log.txt');
        }
        fwrite($fd, "\n");
        fwrite($fd, date('Y-m-d H:i:s: '));
        fwrite($fd, $message);
        flock($fd, LOCK_UN);
        fclose($fd);
    }
}

// EOF