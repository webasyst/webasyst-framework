<?php

/** Ad hoc logging to /wa-log/$file */
class waLog
{
    public static function log($message, $file = 'error.log') {
    	$path = waSystem::getInstance()->getConfig()->getPath('log').'/'.$file;
        $fd = fopen($path, 'a');
        if (!flock($fd, LOCK_EX)) {
            throw new waException('Unable to lock '.$path);
        }
        fwrite($fd, "\n");
        fwrite($fd, date('Y-m-d H:i:s: '));
        fwrite($fd, $message);
        flock($fd, LOCK_UN);
        fclose($fd);
    }
}

// EOF
