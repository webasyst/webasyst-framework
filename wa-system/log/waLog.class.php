<?php
/**
 * Helper class to log to wa-log directory.
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
     * @param mixed $message Message to be logged.
     * @param string $file Name of log file relative to wa-log directory.
     * @return boolean Logging completion status.
     */
    public static function log($message, $file = 'error.log')
    {
        // Make sure it's within allowed directory
        if (preg_match('~\.\.~', $file)) {
            return false;
        }

        self::loadPath();
        $file = self::$path.$file;

        if (!file_exists($file)) {
            if (substr($file, -4) != '.log') {
                // Never create new files with extension other than .log
                $file = self::$path.'error.log';
            }
            waFiles::create(dirname($file), true);
            touch($file);
            chmod($file, 0666);
        } elseif (!is_writable($file)) {
            return false;
        }

        $result = false;
        $fd = fopen($file, 'a');
        if (flock($fd, LOCK_EX)) {
            if (is_object($message)) {
                try {
                    $message = (string) $message;
                } catch (Throwable $e) {
                    $message = (array) $message;
                }
            }
            if (is_array($message)) {
                $message = wa_dump_helper($message);
            }
            $result = fwrite($fd, PHP_EOL.date('Y-m-d H:i:s').' '.waRequest::getIp().PHP_EOL.$message.PHP_EOL);
            fflush($fd);
            flock($fd, LOCK_UN);
        }
        fclose($fd);

        return $result !== false;
    }

    /**
     * Debugging helper to dump any number of variables to a log file.
     *
     * @param mixed $var... any number of arguments to be logged
     * @param string $file Last argument is treated as filename relative to wa-log directory, if it's a string ending with '.log'.
     * @return boolean Logging completion status.
     */
    public static function dump($var, $file = 'dump.log')
    {
        $args = func_get_args();
        if (count($args) > 1) {
            $last_arg = end($args);
            if (is_string($last_arg) && substr($last_arg, -4) === '.log') {
                $file = array_pop($args);
                $vars = $args;
            } else {
                $vars = $args;
                $file = 'dump.log';
            }
        } else {
            $vars = array($var);
        }

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

        foreach ($vars as $var) {
            $result .= wa_dump_helper($var)."\n";
        }

        return waLog::log($result, $file);
    }

    /**
     * Delete a log file inside wa-log directory.
     *
     * @param string $file Name of log file relative to wa-log directory.
     * @return null
     */
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
            self::$path = realpath(self::$path).DIRECTORY_SEPARATOR;
        }
    }
}
