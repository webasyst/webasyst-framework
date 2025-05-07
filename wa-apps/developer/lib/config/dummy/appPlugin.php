<?php
return <<<PHP
<?php

/**
 * Plugin "{$info['name']}" of application "{$params['app_id']}" of Webasyst framework.
 *
 * @license proprietary
 */
class {$class} extends {$parentClass}
{
PHP
. <<<'PHP'
    /**
     * @var waSystem the plugin application
     */
    private $app;

    /**
     * Gets plugin application.
     */
    private function getApp(): waSystem
    {
        if ($this->app === null) {
            $this->app = wa($this->app_id);
        }
        return $this->app;
    }

    /**
     * Gets path to plugin directory or file.
     */
    private function getPath(string $subPath = ''): string
    {
        if ($subPath !== '') {
            $subPath = str_replace('/', DIRECTORY_SEPARATOR, ltrim($subPath, '\/'));
        }
        return $this->path . DIRECTORY_SEPARATOR . $subPath;
    }

    /**
     * Writes data into plugin's log file.
     *
     * @param mixed $data the data to log
     * @param string $logFile the file name without '.log' extension
     * @param bool $rewrite rewrite log?
     * @return void
     */
    private function log($data, string $logFile = 'errors', bool $rewrite = false)
    {
        $path = $this->getPath($logFile . '.log');
        if ($rewrite) {
            waLog::delete($path);
        }
        waLog::dump($data, $path);
    }

    public function getControls($params = []): array
    {
        // waHtmlControl::registerControl(control type/name, [$this, method name]);

        return parent::getControls($params);
    }
}

PHP;
