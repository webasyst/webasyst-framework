<?php

class waUtils
{
	public static function varExportToFile($var, $file)
	{
	    if (!file_exists($file) || is_writable($file)) {
    		if ($h = @fopen($file, 'w+')) {
    			flock($h, LOCK_EX);
    			$result = fwrite($h, "<?php\nreturn ".var_export($var, true).";");
    			flock($h, LOCK_UN);
    			fclose($h);
    			return $result;
    		}
	    }
	    return false;
	}
}