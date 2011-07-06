<?php

/** Ad hoc logging to /wa-log/$file */
class waLog
{
    public static function log($message, $file = 'error.log') {
        $fd = fopen(waSystem::getInstance()->getConfig()->getPath('log').'/'.$file, 'a');
        if (!flock($fd, LOCK_EX)) {
            throw new waException('Unable to lock '.waSystem::getInstance()->getConfig()->get('root_path').'/'.$file);
        }
        fwrite($fd, "\n");
        fwrite($fd, date('Y-m-d H:i:s: '));
        fwrite($fd, $message);
        flock($fd, LOCK_UN);
        fclose($fd);
    }
}

// EOF
