<?php
/*
 * Basic system exception.
 * Базовое системное исключение.
 *
 * @package   wa-system
 * @category  exception
 * @author    Webasyst LLC
 * @copyright 2014 Webasyst LLC
 * @license   http://webasyst.com/framework/license/ LGPL
 */
class waException extends Exception
{
    // Number of padding lines, uses as defalut radius in self::getFileContext()
    const CONTEXT_RADIUS = 5;

    /**
     * Returns string, highlighting specific line of file, 
     * with some number of lines padded above and below.
     * Возвращает строку, выделяя определенную строку файла, 
     * с некоторым количеством строк до и после.
     *
     * @param  string $file   File to open
     * @param  int    $line   Line number
     * @param  int    $radius Number of padding lines
     * @return string|bool FALSE if file unavailable
     */
    public function getFileContext($file, $line = 0, $radius = self::CONTEXT_RADIUS)
    {
        if (!$file || !is_readable($file)) {
            // File unavailable
            return false;
        }

        $line = max(0, $line);

        // Min radius - 1 string
        $radius = min(1, $radius);

        // Open the file and set the line position
        $file = fopen($file, 'r');
        $i = 0;

        // Set the reading range
        $range = array(
            'start' => $line - $radius, 
            'end'   => $line + $radius
        );

        // Set the zero-padding amount for line numbers
        $format = '% '.strlen($range['end']).'d';

        $context = '';

        while ($row = fgets($file)) {
            // Increment the line number
            if (++$i > $range['end']) {
                break;
            }

            if ($i >= $range['start']) {
                $context .= ($i == $line ? ' >>' : '   ').sprintf($format, $i);
                $context .= "\t".htmlspecialchars($row, ENT_NOQUOTES).PHP_EOL;
            }
        }

        fclose($file);

        return $context;
    }

    /**
     * Throw exception "HTTP 500 server error", 
     * uses dump of arguments as message.
     * Вызывает исключение "HTTP 500 ошибка сервера", 
     * использует инфо аргументов в качестве сообщения.
     *
     * @param  mixed ...,
     * @return void
     * @throws waException
     */
    public static function dump()
    {
        $message = '';
        foreach (func_get_args() as $arg) {
            $message .= wa_dump_helper($arg).PHP_EOL;
        }
        throw new self($message, 500);
    }

    /**
     * Return error message.
     * Возвращает сообщение об ошибке.
     * 
     * @return string
     */
    public function __toString()
    {
        try {
            $wa = wa();
            $extra_message = '';
        } catch (Exception $e) {
            $wa = null;
            $extra_message = $e->getMessage();
        }

        /** 
         * Basic error
         */
        if (!waSystemConfig::isDebug() && $wa) {
            $path = realpath(dirname(__FILE__).'/data/').'/';
            $file = $this->getCode().'.php';
            if (!file_exists($path.$file)) {
                $file = 'error.php';
            }
            return include $path.$file;
        }

        /** 
         * CLI error
         */
        if (PHP_SAPI == 'cli' || ($wa && $wa->getEnv() == 'cli')) {
            if ($extra_message) {
                $extra_message = 'Error while initializing waSystem during error generation: '.$extra_message.PHP_EOL;
            }
            return date('Y-m-d H:i:s')." php ".implode(' ', waRequest::server('argv')).PHP_EOL
                .'Error: '.$this->getMessage().PHP_EOL
                .'with code '.$this->getCode().' in '.$this->getFile().' around line '.$this->getLine().':'.PHP_EOL
                .$this->getFileContext($this->getFile(), $this->getLine()).PHP_EOL
                .$this->getTraceAsString().PHP_EOL
                .$extra_message;
        }

        /** 
         * HTTP 404 error
         */
        if ($this->getCode() == 404) {
            $response = new waResponse;
            $response->setStatus(404)
            $response->sendHeaders();
        }

        if ($extra_message) {
            $extra_message = '<div>'
                .'<h2>Error while initializing waSystem during error generation</h2>'
                .'<pre>'.$extra_message.'</pre>'
                .'</div>';
        }

        return '<div style="width:99%;position:relative">'
            .'<h2 id="Title">'.nl2br($this->getMessage()).'</h2>'
            .'<div id="Context" style="display:block">'
            .'<h3>Error with code '.$this->getCode().' in '.$this->getFile().' around line '.$this->getLine().':</h3>'
            .'<pre>'.$this->getFileContext($this->getFile(), $this->getLine()).'</pre>'
            .'</div>'
            .'<div id="Trace"><h2>Call stack</h2><pre>'.$this->getTraceAsString().'</pre></div>'
            .'<div id="Request"><h2>Request</h2><pre>'.var_export($_REQUEST, true).'</pre></div>'
            .'<div id="Params">><h2>Params</h2><pre>'.var_export(waRequest::param(), true).'</pre></div>'
            .$extra_message;
    }
}
