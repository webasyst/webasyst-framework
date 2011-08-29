<?php

class waUtils
{
	public static function varExportToFile($var, $file)
	{
	    if (!file_exists($file) || is_writable($file)) {
    		$h = fopen($file, 'w+');
    		if ($h) {
    			fwrite($h, "<?php\nreturn ".var_export($var, true).";");
    			fclose($h);
    		}
    		return $var;
	    }
	}
}