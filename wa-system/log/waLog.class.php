<?php
/**
 * Add hoc logging to "/wa-log/$file".
 *
 */
class waLog
{
    /**
     * @var string Path to log folder
     */
    protected static $path;

    /**
     * Write message into log file.
     *
     * @return bool
     */
    public static function log($message, $file = 'error.log')
    {
        if (!self::$path) {
            self::$path = waConfig::get('wa_path_log');
            if (!self::$path) {
                self::$path = wa()->getConfig()->getRootPath().DIRECTORY_SEPARATOR.'wa-log';
            }
            self::$path .= DIRECTORY_SEPARATOR;
        }

        $file = self::$path.$file;

        if (!file_exists($file)) {
            waFiles::create($file);
            touch($file);
            chmod($file, 0666);
        } elseif (!is_writable($file)) {
            return false;
        }

        $fd = fopen($file, 'a');
        if (flock($fd, LOCK_EX)) {
            fwrite($fd, PHP_EOL.date('Y-m-d H:i:s:').PHP_EOL.$message);
            fflush($fd);
            flock($fd, LOCK_UN);
        }
        fclose($fd);

        return true;
    }
}
