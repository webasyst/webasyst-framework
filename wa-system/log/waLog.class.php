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
        self::loadPath();
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

    public static function dump($var, $file = 'dump.log')
    {
        $result = '';
        // Show where we've been called from
        if(function_exists('debug_backtrace')) {
            $result .= "dumped from ";
            foreach(debug_backtrace() as $row) {
                if (ifset($row['file']) == __FILE__ || (empty($row['file']) && ifset($row['function']) == 'wa_dumpc')) {
                    continue;
                }
                $result .= ifset($row['file'], '???').' line #'.ifset($row['line'], '???').":\n";
                break;
            }
        }

        $result .= wa_dump_helper($var)."\n";

        waLog::log($result, $file);
    }

    public static function delete($file)
    {
        self::loadPath();
        $file = preg_replace('!\.\.[/\\\]!','', $file);
        $file = self::$path.$file;
        if (file_exists($file)) {
            waFiles::delete($file);
        }
    }

    protected static function loadPath()
    {
        if (!self::$path) {
            self::$path = waConfig::get('wa_path_log');
            if (!self::$path) {
                self::$path = wa()->getConfig()->getRootPath().DIRECTORY_SEPARATOR.'wa-log';
            }
            self::$path .= DIRECTORY_SEPARATOR;
        }
    }
}
