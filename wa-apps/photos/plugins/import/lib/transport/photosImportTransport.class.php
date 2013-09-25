<?php

class photosImportTransport
{

    const LOG_DEBUG = 5;
    const LOG_INFO = 4;
    const LOG_NOTICE = 3;
    const LOG_WARNING = 2;
    const LOG_ERROR = 1;

    protected $log_level = self::LOG_DEBUG;

    protected $options = array();

    /**
     * @var waModel
     */
    protected $dest;

    public function __construct($options = array())
    {
        $this->initOptions();
        foreach($options as $k => $v) {
            $this->options[$k]['value'] = $v;
        }
        $this->dest = new waModel();
    }

    public function initOptions()
    {

    }

    public function init()
    {

    }

    public function step(&$current)
    {

    }

    public function count()
    {
        return 0;
    }

    public function log($message, $level = self::LOG_WARNING)
    {
        if ($level <= $this->log_level) {
            waLog::log($message, 'import.log');
        }
    }

    public function restore()
    {

    }

    public function __wakeup()
    {
        $this->dest = new waModel();
    }


    public function getControls()
    {
        $controls = array();

        $params = array();
        $params['title_wrapper'] = '<div class="name">%s</div>';
        $params['description_wrapper'] = '<br><span class="hint">%s</span><br>';
        $params['control_separator'] = '</div><br><div class="value no-shift">';

        $params['control_wrapper'] = <<<HTML
<div class="field">
%s
<div class="value no-shift">
	%s
	%s
</div>
</div>
HTML;
        foreach ($this->options as $field => $properties) {
            $controls[$field] = waHtmlControl::getControl($properties['settings_html_function'], $field, array_merge($properties, $params));
        }
        return $controls;
    }

}