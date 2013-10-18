<?php

/** Ad hoc logging to /wa-log/$file */
class waLog
{
    public static function log($message, $file = 'error.log') {
        try {
            $wa = wa();
            $path = $wa->getConfig()->getPath('log').'/'.$file;
        } catch (Exception $e) {
            $path = dirname(dirname(dirname(__FILE__))).'/wa-log/'.$file;
        }

        if (!file_exists($path)) {
            waFiles::create($path);
            touch($path);
            chmod($path, 0666);
        } elseif (!is_writable($path)) {
            return false;
        }

        $fd = fopen($path, 'a');
        if (!flock($fd, LOCK_EX)) {
            throw new waException('Unable to lock '.$path);
        }
        fwrite($fd, "\n");
        fwrite($fd, date('Y-m-d H:i:s: '));
        fwrite($fd, $message);
        flock($fd, LOCK_UN);
        fclose($fd);
        return true;
    }
}

// EOF
