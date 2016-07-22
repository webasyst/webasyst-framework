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
     * Save a message to a log file.
     *
     * @param variant $message Message to be logged.
     * @param string $file Name of log file.
     * @param string $once Whether logging must be done only once, only to a non-existent file.
     * @return boolean Logging completion status.
     */
    public static function log($message, $file = 'error.log', $once = false)
    {
        self::loadPath();
        $file = self::$path.$file;
        
        $file_existed = file_exists($file);

        if (!$file_existed) {
            waFiles::create($file);
            touch($file);
            chmod($file, 0666);
        } elseif (!is_writable($file)) {
            return false;
        }

        if (!$once || !$file_existed) {
            $fd = fopen($file, 'a');
            if (flock($fd, LOCK_EX)) {
                fwrite($fd, PHP_EOL.date('Y-m-d H:i:s:').PHP_EOL.$message);
                fflush($fd);
                flock($fd, LOCK_UN);
            }
            fclose($fd);
        }

        return true;
    }

    public static function dump($var, $file = 'dump.log', $once = false)
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

        waLog::log($result, $file, $once);
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
