<?php
/**
 * Helper.
 * 
 * @package    Webasyst
 * @category   System/Utilites
 * @author     Webasyst
 * @copyright  (c) 2011-2014 Webasyst
 * @license    LGPL
 */
class waUtils
{
    /**
     * Write variable in file, it`s uses as easy data storage.
     *
     * @param   mixed   $variable
     * @param   string  $file      Path to file
     * @param   bool    $export    Convert variable in string representation?
     * @param   int     $chmod     Access mode for new directory, uses only if path to file not exist 
     * @return  bool
     */
    public static function varExportToFile($variable, $file, $export = true, $chmod = 0755)
    {
        if (!file_exists($file)) {
            $directory = dirname($file);
            // Create path if no exists
            if (!is_dir($directory) && !mkdir($directory, $chmod, true)) {
                return false;
            }
        } elseif (!is_writable($file)) {
            return false;
        }

        $handle = fopen($file, 'w+');

        if (!is_resource($handle) || !flock($handle, LOCK_EX)) {
            return false;
        }
        
        if ($export) {
            // Detect array
            $is_array = is_array($variable);
            $variable = var_export($variable, true);
            if ($is_array) {
                 // Convert array in human readable format
                $variable = strtr($variable, array("=> \n" => '=>', 'array (' => 'array(', '  ' => '	'));
            }
        }

        $result = fwrite($handle, '<?php'.PHP_EOL.'return '.$variable.';');

        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $result;
    }
}
