<?php

/**
 *
 * @author Webasyst
 *
 */
class waHtmlControl
{
    const INPUT = 'input';
    const FILE = 'file';
    const TEXTAREA = 'textarea';
    const PASSWORD = 'password';
    const RADIOGROUP = 'radiogroup';
    const SELECT = 'select';
    const CHECKBOX = 'checkbox';
    const GROUPBOX = 'groupbox';
    const INTERVAL = 'interval';
    const CONTACT = 'contact';
    const CONTACTFIELD = 'contactfield';
    const HELP = 'help';
    const TITLE = 'title';
    const CUSTOM = 'custom';
    const HIDDEN = 'hidden';
    const DATETIME = 'datetime';

    static private $predefined_controls = array();
    static private $custom_controls = array();
    static private $instance = null;

    static private $wrappers = array(
        'title_wrapper'       => '%s:&nbsp;',
        'description_wrapper' => '<br>%s<br>',
        'control_wrapper'     => "%s\n%s\n%s\n",
        'control_separator'   => "<br>",
    );

    private function __construct()
    {
        self::$predefined_controls = array(
            self::INPUT,
            self::FILE,
            self::TEXTAREA,
            self::PASSWORD,
            self::RADIOGROUP,
            self::SELECT,
            self::CHECKBOX,
            self::HIDDEN,
            self::GROUPBOX,
            self::CONTACT,
            self::CONTACTFIELD,
            self::HELP,
            self::TITLE,
            self::CUSTOM,
            self::INTERVAL,
        );
    }

    public static $default_charset = 'utf-8';

    private function __clone()
    {
    }

    /**
     *
     * @return waHtmlControl
     */
    private static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get control html code
     *
     * @throws Exception
     * @param string $type Type of control (use standard or try to found registered control types) (also support raw control type)
     * @param string $name Control name
     * @param array $params
     * @param array [string]mixed $params Control params
     * @param array [string]mixed $params['namespace'] array of string or string with control namespace
     * @param array [string]mixed $params['class'] array of string or string with control CSS class
     * @param array [string]mixed $params['style'] HTML property of control
     * @param array [string]mixed $params['value']
     * @param array [string]mixed $params['size'] HTML property of control
     * @param array [string]mixed $params['maxlength'] HTML property of control
     * @param array [string]mixed $params['cols'] HTML property of control
     * @param array [string]mixed $params['rows'] HTML property of control
     * @param array [string]mixed $params['wrap']
     * @param array [string]mixed $params['callback']
     * @param array [string]array $params['options'] variants for selectable control
     * @param array [string][][string]string $params['options']['title'] variant item title for selectable control
     * @param array [string][][string]mixed $params['options']['value'] variant item value for selectable control
     *
     * @param array [string]string $params['title_wrapper'] title output format format
     * @param array [string]string $params['description_wrapper'] description output format
     * @param array [string]string $params['control_wrapper'] control output format
     * @param array [string]string $params['control_separator'] control items separator
     *
     * @return string
     */
    public static function getControl($type, $name, $params = array())
    {
        if (!is_array($params)) {
            throw new waException('Invalid function params at '.__METHOD__.' expect $params to be array');
        }
        #raw type support
        if (preg_match('/^([\w]+)(\s+.*)$/', $type, $matches)) {
            $type = $matches[1];
            $options = trim($matches[2]);
            switch ($type) {
                case self::CUSTOM:
                    if (preg_match('/^([\w:]+)(.*)$/', $options, $matches)) {
                        $params['callback'] = $matches[1];
                        if (preg_match('/^[\w]+::[\w]+$/', $params['callback'])) {
                            $params['callback'] = explode('::', $params['callback']);
                        }
                        $options = trim($matches[2]);
                    }
                    break;
            }

            #transform raw options data into array
            if (!isset($params['options'])) {
                $params['options'] = self::getControlParams($options);
            }
        } elseif (!empty($params['options_callback']) && !isset($params['options'])) {
            $params['options'] = self::getControlParams($params['options_callback']);
            unset($params['options_callback']);
        }


        #input exception handler support
        //TODO check and complete code
        if (isset($params['wrong_value'])) {
            if (false && isset($params['value']) && is_array($params['value']) && is_array($params['wrong_value'])) {
                $params['value'] = array_merge($params['value'], $params['wrong_value']);
            } else {
                $params['value'] = $params['wrong_value'];
            }
        }

        #namespace workaround
        if (isset($params['name'])) {
            $name = $name ? $name : $params['name'];
            unset($params['name']);
        }
        $namespace = self::makeNamespace($params);
        if ($namespace !== null) {
            $name = "{$namespace}[{$name}]";
            unset($params['namespace']);
        }

        #usage dual standard for options value=>title
        if (isset($params['options']) && is_array($params['options'])) {
            foreach ($params['options'] as $key => & $data) {
                if (!is_array($data)) {
                    //TODO check format usage
                    $data = array('title' => $data, 'value' => $key);
                }
                unset($data);
            }
        }

        #type aliases
        switch ($type) {
            case 'text':
                $type = self::INPUT;
                break;
            default:
                break;
        }

        $control_name = "get".ucfirst($type)."Control";
        if (!isset($params['class'])) {
            $params['class'] = array();
        } elseif (!is_array($params['class'])) {
            $params['class'] = array($params['class']);
        }

        $params['class'][] = $type;

        $original_wrappers = self::getControlWrappers($params);
        self::makeId($params, $name);
        $instance = self::getInstance();
        $passed_params = $params;
        $ref = &$passed_params;
        $control = $instance->$control_name($name, $ref);
        $custom_wrappers = self::getControlCustomWrappers($ref);
        $params = $custom_wrappers + $params;
        self::getControlWrappers($params, $original_wrappers);

        if (is_array($control)) {
            $controls = array_values($control);
            $control = '';
            foreach ($controls as $id => $chunk) {
                $control .= sprintf($params['control_wrapper'], $chunk['title'], $chunk['control'], $chunk['description']);
                if ($control === false) {
                    $control = "Invalid param 'control_wrapper' for {$type}:{$name}";
                    break;
                }
                if ($id < count($controls)) {
                    $control .= $params['control_separator'];
                }
            }
        } elseif (($type !== waHtmlControl::HIDDEN)
            &&
            !in_array(ifset($params['subtype']), array(waHtmlControl::HIDDEN), true)
        ) {
            $control = sprintf($params['control_wrapper'], self::getControlTitle($params), $control, self::getControlDescription($params));
            if ($control === false) {
                $control = "Invalid param 'control_wrapper' for {$type}:{$name}";
            }
        }
        return $control;
    }

    private static function getControlCustomWrappers($params)
    {
        $wrappers = array();

        foreach (self::$wrappers as $wrapper_name => $wrapper) {
            $custom_wrapper_name = 'custom_'.$wrapper_name;
            if (isset($params[$custom_wrapper_name])) {
                $wrappers[$custom_wrapper_name] = $params[$custom_wrapper_name];
            }
        }
        return $wrappers;
    }

    private static function getControlWrappers(&$params, $passed_wrappers = array())
    {
        $wrappers = array();

        foreach (self::$wrappers as $wrapper_name => $wrapper) {
            $custom_wrapper_name = 'custom_'.$wrapper_name;

            if (isset($params[$wrapper_name])) {
                $wrappers[$wrapper_name] = $params[$wrapper_name];
            }

            if (isset($params[$custom_wrapper_name])) {
                $params[$wrapper_name] = $params[$custom_wrapper_name];
            } else {
                if (isset($passed_wrappers[$wrapper_name])) {
                    $params[$wrapper_name] = $passed_wrappers[$wrapper_name];
                } elseif (!isset($params[$wrapper_name])) {
                    $params[$wrapper_name] = $wrapper;
                }
            }
        }
        return $wrappers;
    }

    /**
     * @param $raw_params string raw control params in CSV format
     * @return array
     */
    private static function getControlParams($raw_params)
    {
        $options = null;
        if (is_array($raw_params)) {
            $callback = array_shift($raw_params);
            if (is_object($callback) && ($class = get_class($callback))) {
                $callback = array($callback);
                $callback[] = array_shift($raw_params);
                if (in_array($callback[1], get_class_methods($class))) {
                    $options = call_user_func_array($callback, $raw_params);
                }
            } elseif (function_exists($callback)) {
                $options = call_user_func_array($callback, $raw_params);
            } elseif (is_string($callback) && class_exists($callback)) {
                $callback = array($callback);
                $callback[] = array_shift($raw_params);
                if (in_array($callback[1], get_class_methods($callback[0]))) {
                    $options = call_user_func_array($callback, $raw_params);
                }
            }
        } elseif (preg_match('/^([_\w]+)::([_\w]+)(\s+.+)?$/', $raw_params, $matches) && class_exists($matches[1]) && in_array($matches[2], get_class_methods($matches[1]))) {
            $callback = array($matches[1], $matches[2]);
            $options = isset($matches[3]) ? call_user_func($callback, $matches[3]) : call_user_func($callback);
        } elseif (preg_match('/([_\w]+)(.*)/', $raw_params, $matches) && function_exists($matches[1])) {
            $options = call_user_func($matches[1], $matches[2]);
        }

        #parse CSV format
        if (!is_array($options) && $raw_params) {
            $csv_pattern = '@(?:,|^)([^",]+|"(?:[^"]|"")*")?@';
            $cell_pattern = '@^"([^"].+[^"])"$@';
            if (preg_match_all($csv_pattern, $raw_params, $matches)) {
                $options = array();
                foreach ($matches[1] as $param) {
                    if (preg_match($cell_pattern, $param, $param_matches)) {
                        $param = $param_matches[1];
                    }
                    $param = str_replace('""', '"', $param);
                    $param = explode(':', $param, 2);
                    $options[] = array('title' => $param[0], 'value' => isset($param[1]) ? $param[1] : $param[0]);
                }
            }
        }
        return $options;
    }

    public function __call($function_name, $args = null)
    {
        if (preg_match('/^get(\w+)Control$/', $function_name, $matches)) {
            $type = $matches[1];
            $name = array_shift($args);
            $params = array_shift($args);

            if (!isset($params['value'])) {
                $params['value'] = isset($params['default']) ? $params['default'] : false;
            }
            if (isset(self::$custom_controls[$type])) {
                return call_user_func_array(self::$custom_controls[$type], array($name, $params));
            } else {
                $message = "Control type <b>{$type}</b> undefined";
                if (self::$custom_controls) {
                    $message .= ", use one of this: ".implode(', ', array_keys(self::$custom_controls));
                }
                return $message;
            }
        } else {
            throw new waException("Call undefined function {$function_name} at ".__CLASS__);
        }

    }

    /**
     * Register user input control
     *
     * @throws Exception
     * @param string $type
     * @param callback $callback
     * @return void
     */
    public static function registerControl($type, $callback)
    {
        if (is_callable($callback)) {
            self::$custom_controls[$type] = $callback;
        } else {
            throw new waException("invalid callback for control type {$type}");
        }
    }

    /**
     *
     * @param $params
     * @param $name
     * @param $id
     * @return string
     */
    final public static function makeId(&$params, $name = '', $id = null)
    {
        static $counter = 0;
        //settings_{$name}_{$id}
        $params['id'] = $id ? $id : ((isset($params['id']) && $params['id']) ? $params['id'] : strtolower(__CLASS__));
        if (isset($params['namespace'])) {
            $params['id'] .= '_'.implode('_', (array)$params['namespace']);
        }
        if ($name) {
            $params['id'] .= "_{$name}";
        } elseif ($name === false) {
            $params['id'] .= ++$counter.'_';
        }
        $params['id'] = preg_replace(array('/[_]{2,}/', '/[_]{1,}$/'), array('_', ''), str_replace(array('[', ']', '.'), '_', $params['id']));
        return $params['id'];
    }

    final public static function makeNamespace($params)
    {
        $namespace = null;
        if (!empty($params['namespace'])) {
            if (is_array($params['namespace'])) {
                $namespace = array_shift($params['namespace']);
                while (($namespace_chunk = array_shift($params['namespace'])) !== null) {
                    $namespace .= "[{$namespace_chunk}]";
                }
            } else {
                $namespace = $params['namespace'];
            }
        }
        return $namespace;
    }

    /**
     * Add namespace for control params
     * name="control_name" became name="namespace[control_name]" for string and name="namespace1[namespace2]...[control_name ] for array
     * @param $params array
     * @param $namespace string|array
     * @return void
     */
    final public static function addNamespace(&$params, $namespace = '')
    {
        if (isset($params['namespace'])) {
            if (!is_array($params['namespace'])) {
                $params['namespace'] = array($params['namespace']);
            }
        } else {
            $params['namespace'] = array();
        }
        foreach ((array)$namespace as $chunk) {
            $params['namespace'][] = $chunk;
        }
    }

    public static function getName(&$params, $name = null)
    {
        if (isset($params['name'])) {
            $name = $name ? $name : $params['name'];
            unset($params['name']);
        }
        $namespace = self::makeNamespace($params);
        if ($namespace !== null) {
            $name = "{$namespace}[{$name}]";
            unset($params['namespace']);
        }
        return $name;
    }

    private static function getControlTitle($params)
    {
        $title = '';
        if (isset($params['title']) && !empty($params['title_wrapper'])) {
            $option_title = self::escape(self::_wp($params['title'], $params));
            if (!empty($params['id']) && strlen($option_title)) {
                $params['id'] = self::escape($params['id']);
                $option_title = sprintf('<label for="%s">%s</label>', $params['id'], $option_title);
            }
            $title = sprintf($params['title_wrapper'], $option_title);
        } elseif ($params['title_wrapper'] === false) {
            $title = null;
        }
        return $title;
    }

    private static function getControlDescription($params)
    {
        $description = '';
        if (!empty($params['description_wrapper']) && !empty($params['description'])) {
            $description = sprintf($params['description_wrapper'], self::_wp($params['description'], $params));
        }
        return $description;
    }

    private function getInputControl($name, $params = array())
    {
        $control = '';
        $control_name = self::escape($name);
        $control .= "<input id=\"{$params['id']}\" type=\"text\" name=\"{$control_name}\" ";
        if (isset($params['format_description'])) {
            $params['format_description'] = self::_wp($params['format_description']);
        }
        $map = array(
            'class',
            'style',
            'size',
            'maxlength',
            'title',
            'value',
            'placeholder',
            'readonly',
            'required',
            'disabled',
            'autocomplete',
            'autofocus',
            'format'             => 'data-regexp',
            'format_description' => 'data-regexp-hint',
        );
        $control .= self::addCustomParams($map, $params);
        $control .= ">";
        return $control;
    }

    private function getHiddenControl($name, $params = array())
    {
        $control_name = self::escape($name);
        $control = "<input type=\"hidden\" name=\"{$control_name}\" ";
        $control .= self::addCustomParams(array('id', 'class', 'title', 'value', 'disabled'), $params);
        $control .= ">";
        return $control;
    }

    private function getFileControl($name, $params = array())
    {
        $control = '';
        $control_name = self::escape($name);
        $control .= "<input type=\"file\" name=\"{$control_name}\" ";
        $control .= self::addCustomParams(array('class', 'style', 'id'), $params);
        $control .= ">";
        if (!empty($params['value'])) {
            if (!empty($params['img_path'])) {
                $path = wa()->getDataPath($params['img_path'], true);
                if (file_exists($path.'/'.$params['value'])) {
                    $url = wa()->getDataUrl($params['img_path'], true);
                    $file = self::escape($url.$params['value'], ENT_NOQUOTES);
                    $control .= "<br/><span><img src=\"{$file}\"></span>";
                } else {
                    $file = self::escape($params['value'], ENT_NOQUOTES);
                    $control .= "<br/><span>{$file}</span>";
                }
            } else {
                $file = self::escape($params['value'], ENT_NOQUOTES);
                $control .= "<br/><span>{$file}</span>";
            }
        }
        return $control;
    }

    private function getTextareaControl($name, $params = array())
    {
        $control = '';
        $control_name = self::escape($name);
        $value = self::escape($params['value']);
        $control .= "<textarea name=\"{$control_name}\"";
        $control .= self::addCustomParams(array('class', 'style', 'cols', 'rows', 'wrap', 'id', 'title', 'placeholder', 'readonly', 'autofocus', 'disabled'), $params);
        $control .= ">{$value}</textarea>";

        if (empty($params['wysiwyg']) && !empty($params['wisywig'])) {
            $params['wysiwyg'] = $params['wisywig'];
        }

        if (!empty($params['wysiwyg'])) {
            if (!is_array($params['wysiwyg'])) {
                $params['wysiwyg'] = array();
            }
            $params['wysiwyg'] += array(
                'mode'         => 'text/html',
                'tabMode'      => 'indent',
                'height'       => 'dynamic',
                'lineWrapping' => 'true',
            );
            $options = json_encode($params['wysiwyg']);
            $control .= <<<HTML
<style type="text/css">
    .CodeMirror {
        border: 1px solid #ABADB3;
    }
</style>
<script type="text/javascript">
    if(typeof(CodeMirror) == 'function') {
        var textarea = document.getElementById('{$params['id']}'), 
            onchange = {
                'onChange':function(cm) {
                    textarea.value = cm.getValue();
                }
            };
        setTimeout(function(){
            CodeMirror.fromTextArea(textarea, $.extend($options, onchange));
        }, 500);
    }
</script>
HTML;
        }

        return $control;
    }

    private function getHelpControl($name, $params = array())
    {
        $control = '';
        $control_name = self::escape($name);
        $value = self::escape($params['value']);
        $control .= "<p name=\"{$control_name}\"";
        $control .= self::addCustomParams(array('id', 'class', 'style',), $params);
        $control .= ">{$value}</p>";
        return $control;
    }

    private function getTitleControl($name, &$params = array())
    {
        $control = '';
        $control_name = self::escape($name);
        $value = self::escape($params['value']);
        $control .= "<h3 name=\"{$control_name}\"";
        $control .= self::addCustomParams(array('id', 'class', 'style',), $params);
        $control .= ">{$value}</h3>";
        if (!isset($params['custom_description_wrapper'])) {
            $params['custom_description_wrapper'] = '<h4 class="hint">%s</h4>';
        }
        return $control;
    }

    /**
     * @todo hide real value for password
     * @param $name
     * @param $params
     * @return string
     */
    private function getPasswordControl($name, $params = array())
    {
        $control = '';
        $control_name = self::escape($name);
        $control .= "<input type=\"password\" name=\"{$control_name}\"";
        $control .= self::addCustomParams(array('id', 'class', 'style', 'size', 'maxlength', 'title', 'value', 'placeholder', 'readonly', 'autofocus', 'disabled'), $params);
        $control .= ">";
        return $control;
    }

    private function getRadiogroupControl($name, $params = array())
    {
        $control = '';
        $id = 0;
        $value = self::escape($params['value']);
        $options = isset($params['options']) ? (is_array($params['options']) ? $params['options'] : array($params['options'])) : array();
        foreach ($options as $option) {
            ++$id;
            $option_value = $option['value'];
            if ($option_value == $value) {
                $params['checked'] = 'checked';
            } elseif (isset($params['checked'])) {
                unset($params['checked']);
            }
            self::makeId($params, $name, md5($option_value));
            $option_value = self::escape($option_value);
            $control_name = self::escape($name);
            $control .= "<input type=\"radio\" name=\"{$control_name}\" value=\"{$option_value}\"";
            $control .= self::addCustomParams(array('id', 'class', 'style', 'checked', 'readonly', 'disabled',), $params + $option);
            if (!empty($option['title'])) {
                $option_title = self::escape(self::_wp($option['title'], $params));
                $control .= ">&nbsp;<label";
                $control .= self::addCustomParams(array('id' => 'for',), $params);
                $control .= self::addCustomParams(array('description' => 'title', 'class', 'style',), $option);
                $control .= ">{$option_title}</label>\n";
            } else {
                $control .= ">\n";
            }

            $control .= self::getControlDescription(array_merge($params, array('description' => null), $option));
            if ($id < count($options)) {
                $control .= $params['control_separator'];
            }
        }
        return $control;
    }

    private function getSelectControl($name, $params = array())
    {
        $control = '';
        $id = 0;
        $options = isset($params['options']) ? (is_array($params['options']) ? $params['options'] : array($params['options'])) : array();
        $control .= "<select name=\"{$name}\" autocomplete=\"off\"";
        $control .= self::addCustomParams(array('id', 'class', 'style', 'title', 'readonly', 'autofocus', 'disabled'), $params);
        $control .= ">\n";
        $group = null;
        foreach ($options as $option) {
            if ($group && (empty($option['group']) || (strcasecmp($option['group'], $group) != 0))) {
                $group = false;
                $control .= "\n</optgroup>\n";
            }
            if (!empty($option['group']) && ($option['group'] != $group)) {
                $group = (string)$option['group'];
                $custom_params = self::addCustomParams(
                    array(
                        'class'       => 'group_class',
                        'group_style' => 'style',
                    ),
                    $option
                );
                $control .= "\n<optgroup label=\"".self::escape($group)."\"".$custom_params.">\n";
            }

            ++$id;
            $option_value = $option['value'];
            if (isset($params['value']) && ($option_value == $params['value'])) {
                $params['selected'] = 'selected';
            } elseif (isset($params['selected'])) {
                unset($params['selected']);
                unset($params['value']);
            }
            if (isset($option['description'])) {
                $params['description'] = $option['description'];
            }
            $option_value = self::escape($option_value);
            $control .= "<option value=\"{$option_value}\"";
            $control .= self::addCustomParams(array('selected'), $params);
            $control .= self::addCustomParams(array('class', 'style', 'disabled', 'description' => 'title',), $option);
            $option_title = self::escape(self::_wp(ifset($option['title'], $option_value), $params));
            $control .= ">{$option_title}</option>\n";
        }
        if ($group) {
            $control .= "\n</optgroup>\n";
        }
        $control .= "</select>";
        return $control;
    }

    private function getGroupboxControl($name, $params = array())
    {
        $control = '';
        $options = isset($params['options']) ? (is_array($params['options']) ? $params['options'] : array($params['options'])) : array();
        if (!is_array($params['value'])) {
            $params['value'] = array();
        }
        self::addNamespace($params, $name);
        $wrappers = ifempty($params['options_wrapper'], array()) + array(
                'title_wrapper'       => '&nbsp;%s',
                'description_wrapper' => '<span class="hint">%s</span>',
                'control_wrapper'     => '%2$s'."\n".'%1$s'."\n".'%3$s'."\n",
                'control_separator'   => "<br>",

            );
        unset($params['options_wrapper']);
        $params = array_merge($params, $wrappers);
        $checkbox_params = $params;
        if (isset($params['options'])) {
            unset($checkbox_params['options']);
        }
        $id = 0;
        foreach ($options as $option) {
            $checkbox_params['value'] = !empty($option['value']) ? $option['value'] : 1;
            $checkbox_params['checked'] = in_array($option['value'], $params['value'], true) || !empty($params['value'][$option['value']]);
            $checkbox_params['title'] = empty($option['title']) ? null : $option['title'];
            $checkbox_params['description'] = ifempty($option['description']);
            $checkbox_params['disabled'] = ifempty($option['disabled']);
            if ($checkbox_params['disabled'] && !empty($option['checked'])) {
                $checkbox_params['checked'] = true;
            }
            $control .= self::getControl(self::CHECKBOX, $option['value'], $checkbox_params);
            if (++$id < count($options)) {
                $control .= $params['control_separator'];
            }
        }
        return $control;
    }

    private function getIntervalControl($name, $params = array())
    {
        $control = '';
        if (!isset($params['value']) || !is_array($params['value'])) {
            $params['value'] = array();
        }
        $default_params = array(
            'value' => array(
                'from' => '',
                'to'   => '',
            ),
        );
        $params['value'] = array_merge($default_params['value'], $params['value']);
        $input_params = $params;
        self::addNamespace($input_params, $name);
        $input_name = "from";
        $input_params['value'] = $params['value']['from'];
        $input_params['title'] = 'str_from';

        $control .= self::getControl(self::INPUT, $input_name, $input_params)."\n";

        $input_params = $params;
        $input_name = "to";
        self::addNamespace($input_params, $name);
        $input_params['value'] = $params['value']['to'];
        $input_params['title'] = 'str_to';
        $control .= self::getControl(self::INPUT, $input_name, $input_params)."\n";
        return $control;
    }

    private function getCheckboxControl($name, $params = array())
    {
        $control = '';
        $value = isset($params['value']) ? $params['value'] : false;
        if ($value) {
            if (!isset($params['checked'])) {
                $params['checked'] = 'checked';
            }
        } elseif (isset($params['checked'])) {
            unset($params['checked']);
        }
        if (empty($params['value'])) {
            $params['value'] = 1;
        }
        if (isset($params['label']) && $params['label']) {
            $control .= "<label";
            $control .= self::addCustomParams(array('for' => 'id'), $params);
            $control .= ">";
        }
        $control .= "<input type=\"checkbox\" name=\"{$name}\"";
        $control .= self::addCustomParams(array('value', 'class', 'style', 'checked', 'id', 'title', 'disabled',), $params);
        $control .= ">";
        if (isset($params['label']) && $params['label']) {
            $control .= '&nbsp;'.self::escape(self::_wp($params['label'], $params))."</label>";
        }

        return $control;
    }

    private function getContactControl($name, $params = array())
    {
        $control = array();
        if ($name) {
            self::addNamespace($params, $name);
        }
        $params['namespace'] = $namespace = self::makeNamespace($params);
        $contact = wa()->getUser();
        $values = isset($params['value']) ? (array)$params['value'] : array();
        $custom_params = array('class', 'style', 'placeholder', 'id', 'readonly', 'disabled');
        $id = 0;
        foreach ((array)$params['options'] as $field) {
            $params['namespace'] = $namespace;
            $control[$id] = array(
                'title'       => '',
                'control'     => '',
                'description' => '',
            );
            $field_id = is_array($field) ? $field['value'] : $field;
            if (!isset($values[$field_id])) {
                $values[$field_id] = $contact->get($field_id);
            }

            $params['value'] = $values[$field_id];
            if (strpos($field_id, ':')) {
                list($field_id, $sub_field_id) = explode(':', $field_id, 2);
            } else {
                $sub_field_id = null;
            }
            if ($contact_field = waContactFields::get($field_id)) {
                if (is_array($params['value'])) {
                    $params['value'] = current($params['value']);
                }

                self::makeId($params, $field_id);
                $params['title'] = $contact_field->getName();
                $attrs = $this->addCustomParams($custom_params, $params);
                if ($sub_field_id) {
                    self::makeId($params, $sub_field_id);
                    $params['namespace'] = self::makeNamespace($params + array('name'));
                }

                unset($params['id']);
                if ($sub_field_id) {
                    if ($contact_sub_field = $contact_field->getFields($sub_field_id)) {
                        /**
                         * @var waContactField $contact_sub_field
                         */
                        $control[$id]['title'] .= ' '.$contact_sub_field->getName();
                        $params['id'] = "{$field_id}:{$sub_field_id}";
                        $control[$id]['control'] = $contact_sub_field->getHTML($params, $attrs);
                    } else {
                        $params['title'] .= ':'.$sub_field_id;
                        $control[$id]['title'] = $this->getControlTitle($params);
                        $control[$id]['control'] .= sprintf('<span class="error">%s<span>', _w('Contact field not found'));
                    }
                } else {
                    $control[$id]['control'] = $contact_field->getHTML($params, $attrs);

                    $control[$id]['title'] = $this->getControlTitle($params);
                }
            } else {

                $params['title'] = $field_id;
                $control[$id]['title'] = $this->getControlTitle($params);
                $control[$id]['control'] .= sprintf('<span class="error">%s<span>', _w('Contact field not found'));
            }
            ++$id;
        }
        return $control;
    }

    private function getContactfieldControl($name, $params = array())
    {
        $params['options'] = array();

        $params['options'][] = array(
            'title' => '—',
            'value' => '',
        );
        $fields = waContactFields::getAll();
        foreach ($fields as $field) {
            if ($field instanceof waContactCompositeField) {
                /**
                 * @var waContactCompositeField $field
                 */
                $sub_fields = $field->getFields();
                foreach ($sub_fields as $sub_field) {
                    /**
                     * @var waContactField $sub_field
                     */
                    $params['options'][] = array(
                        'group' => $field->getName(),
                        'title' => $sub_field->getName(),
                        'value' => $field->getId().'.'.$sub_field->getId(),
                    );
                }
            } else {
                /**
                 * @var waContactField $field
                 */
                $params['options'][] = array(
                    'title' => $field->getName(),
                    'value' => $field->getId(),
                );
            }
        }
        return $this->getSelectControl($name, $params);

    }

    private function getDatetimeControl($name, $params = array())
    {
        $html = '';

        $wrappers = array(
            'title'           => '',
            'title_wrapper'   => '%s',
            'description'     => '',
            'control_wrapper' => "%s\n%3\$s\n%2\$s\n",
            'id'              => '',
        );

        $params = array_merge($params, $wrappers);
        $available_days = array();
        $date_params = $params;
        $date_format = waDateTime::getFormat('date');
        if (isset($params['params']['date'])) {
            $date_params['style'] = "z-index: 100000;";

            $date_name = preg_replace('@([^\]]+)(\]?)$@', '$1.date_str$2', $name);
            $offset = min(365, max(0, intval(ifset($params, 'params', 'date', 0))));
            $date_params['placeholder'] = waDateTime::getFormatHuman($date_format);
            $date_params['description'] = _ws('Date');

            $date_params['value'] = ifset($params, 'value', 'date_str', '');

            $html .= waHtmlControl::getControl(waHtmlControl::INPUT, $date_name, $date_params);
            self::makeId($date_params, $date_name);

            $date_name = preg_replace('@([^\]]+)(\]?)$@', '$1.date$2', $name);
            $date_formatted_params = $params;
            $date_formatted_params['style'] = "display: none;";
            $date_formatted_params['value'] = ifset($params, 'value', 'date', '');

            $html .= waHtmlControl::getControl(waHtmlControl::INPUT, $date_name, $date_formatted_params);
            self::makeId($date_formatted_params, $date_name);


            $date_format_map = array(
                'PHP' => 'JavaScript',
                'Y'   => 'yy',
                'd'   => 'dd',
                'm'   => 'mm',
            );
            $js_date_format = str_replace(array_keys($date_format_map), array_values($date_format_map), $date_format);
            $locale = wa()->getLocale();
            $localization = sprintf("wa-content/js/jquery-ui/i18n/jquery.ui.datepicker-%s.js", $locale);
            if (!is_readable($localization)) {
                $localization = '';
            }

        }

        $interval_params = $params;
        if (!empty($params['params']['interval'])) {

            $intervals = ifempty($params['params']['intervals'], false);
            if (is_array($intervals)) {
                $interval_params['options'] = array();
                foreach ($intervals as $id => $interval) {
                    if (is_array($interval) && isset($interval['from']) && isset($interval['to'])) {
                        $days = ifset($interval['day'], array());
                        $value = sprintf(
                            '%d:%02d-%d:%02d',
                            $interval['from'],
                            ifset($interval['from_m'], 0),
                            $interval['to'],
                            ifset($interval['to_m'], 0)
                        );
                        $interval_params['options'][$value] = array(
                            'value' => $value,
                            'title' => empty($value) ? _ws('Time') : $value,
                            'data'  => array(
                                'days'  => array_keys($days),
                                'value' => $value,
                            ),
                        );
                        $available_days = array_merge(array_keys($days), $available_days);
                    } else {
                        $interval_params['options'][$id] = array(
                            'value' => $id,
                            'title' => empty($id)?_ws('Time'):$id,
                            'data'  => array(
                                'days'  => $interval,
                                'value' => $id,
                            ),
                        );
                        $available_days = array_merge(array_keys($interval), $available_days);
                    }
                }

                $available_days = array_values(array_unique($available_days));
            }

            $interval_name = preg_replace('@([^\]]+)(\]?)$@', '$1.interval$2', $name);
            $interval_params['description'] = _ws('Time');
            if (isset($interval_params['options'])) {
                $html .= ifset($params['control_separator']);
                if (!isset($interval_params['options'][null])) {
                    $option = array(
                        'value' => '',
                        'title' => _ws('Time'),
                        'data'  => array('days' => $available_days),
                    );
                    array_unshift($interval_params['options'], $option);
                }
                $interval_params['value'] = ifset($params['value']['interval']);
                $html .= waHtmlControl::getControl(self::SELECT, $interval_name, $interval_params);
                self::makeId($interval_params, $interval_name);
            } else {
                $html .= ifset($params['control_separator']);
                $html .= waHtmlControl::getControl(self::INPUT, $interval_name, $interval_params);
            }
        }


        if (isset($params['params']['date']) && isset($offset)) {

            if (empty($interval_params['id'])) {
                $interval_params['id'] = '';
            }

            $available_days = json_encode($available_days);
            $root_url = wa()->getRootUrl();
            $html .= <<<HTML
<script>
    ( function() {
        'use strict';
        var id_date = '{$date_params['id']}';
        var id_date_formatted = '{$date_formatted_params['id']}';
        var date_input = $('#' + id_date);
        var date_formatted = $('#' + id_date_formatted);
        var interval = '{$interval_params['id']}' ? $('#{$interval_params['id']}') : false;
        
        date_input.data('available_days', {$available_days});

        var initDatePicker = function () {
            date_input.datepicker({
                "dateFormat": '{$js_date_format}',
                "minDate": {$offset},
                "onSelect": function (dateText) {
                    var d = date_input.datepicker('getDate');
                    date_input.val(dateText);
                    date_formatted.val($.datepicker.formatDate('yy-mm-dd', d));
                    if (d && interval && interval.length) {
                        /** @var int day week day (starts from 0) */
                        var day = (d.getDay() + 6) % 7;
                        /**
                         * filter select by days
                         */
                        var value = typeof(interval.val()) !== 'undefined';
                        var matched = null;
                        interval.find('option').each(function () {
                            /**
                             * @this HTMLOptionElement
                             */
                            var option = $(this);
                            var disabled = (option.data('days').indexOf(day) === -1) ? 'disabled' : null;
                            option.attr('disabled', disabled);
                            if (disabled) {
                                if (this.selected) {
                                    value = false;
                                }
                            } else {
                                matched = this;
                                if (!value) {
                                    this.selected = true;
                                    value = !!this.value;
                                    if (typeof(interval.highlight) === 'function') {
                                        interval.highlight();
                                    }
                                }
                            }
                        });

                        if (value) {
                            interval.removeClass('error');
                        } else if (matched) {
                            matched.selected = true;
                            interval.removeClass('error');
                        } else {
                            interval.addClass('error');
                        }
                    }

                },
                "beforeShowDay": function (date) {
                    if (interval && interval.length) {
                        /** @var int day week day */
                        var day = (date.getDay() + 6) % 7;
                        var available_days = date_input.data('available_days')||[];
                        return [available_days.indexOf(day) !== -1];
                    } else {
                        return [true];
                    }
                }
            });

            $(".ui-datepicker").each( function() {
                $(this).hide().css({ zIndex: 1000 });
            });
        };

        $(document).ready( function() {
            if (typeof $.fn.datepicker === "function") {
                initDatePicker();

            } else {
                load([
                    {
                        id: "wa-content-jquery-ui-js",
                        type: "js",
                        uri: "{$root_url}wa-content/js/jquery-ui/jquery-ui-1.7.2.custom.min.js"
                    },
                    {
                        id: "wa-content-jquery-ui-css",
                        type: "css",
                        uri: "{$root_url}wa-content/css/jquery-ui/jquery-ui-1.7.2.custom.css"
                    }
                ]).then(function() {

                    var locale = "{$locale}".substr(0, 2);
                    if (locale === "ru") {
                        load([{
                            id: "wa-content-jquery-ui-locale-js",
                            type: "js",
                            uri: "{$root_url}{$localization}"
                        }]).then(initDatePicker);
                    } else {
                        initDatePicker();
                    }
                    
                });
            }
        });
        
        function load(sources) {
                var deferred = $.Deferred();
        
                loader(sources).then( function() {
                    deferred.resolve();
                });
        
                return deferred.promise();
        
                function loader(sources) {
                    var deferred = $.Deferred(),
                        counter = sources.length;
        
                    $.each(sources, function(i, source) {
                        switch (source.type) {
                            case "css":
                                loadCSS(source);
                                break;
                            case "js":
                                loadJS(source);
                                break;
                        }
                    });
        
                    return deferred.promise();
        
                    /**/
        
                    function loadCSS(source) {
                        var link = $("#" + source.id);
                        if (link.length) {
                            link.data("promise").then(onLoad);
        
                        } else {
                            var deferred = $.Deferred(),
                                promise = deferred.promise();
        
                            link = $("<link />", {
                                id: source.id,
                                rel: "stylesheet"
                            }).appendTo("head")
                                .data("promise", promise);
        
                            link.on("load", function() {
                                onLoad();
                                deferred.resolve();
                            });
        
                            link.attr("href", source.uri);
                        }
        
                        function onLoad() {
                            counter -= 1;
                            watcher();
                        }
                    }
        
                    function loadJS(source) {
                        var script = $("#" + source.id);
                        if (script.length) {
                            script.data("promise").then(onLoad);
        
                        } else {
                            var deferred = $.Deferred(),
                                promise = deferred.promise(),
                                script = document.createElement("script");
                                
                            document.getElementsByTagName("head")[0].appendChild(script);
        
                            script = $(script)
                                .attr("id", source.id)
                                .data("promise", promise);
        
                            script.on("load", function () {
                                onLoad();
                                deferred.resolve();
                            });
        
                            script.attr("src", source.uri);
                        }
        
                        function onLoad() {
                            counter -= 1;
                            watcher();
                        }
                    }
        
                    function watcher() {
                        if (counter === 0) {
                            deferred.resolve();
                        }
                    }
                }
            }
    })();
</script>

HTML;
        }

        return $html;
    }

    /**
     * @todo complete params check
     * @param $name
     * @param array $params
     * @throws Exception
     * @return string
     */
    private function getCustomControl($name, $params = array())
    {
        /**
         * @var $callback callback|string
         */
        $callback = isset($params['callback']) ? $params['callback'] : null;
        if ($callback) {
            unset($params['callback']);
            if (is_array($callback)) {
                if (is_object($callback[0])) {
                    if (!method_exists($callback[0], $callback[1])) {
                        throw new waException("Method {$callback[1]} not exists at class ".get_class($callback[0]));
                    }
                } elseif (!class_exists($callback[0])) {
                    throw new waException("Class {$callback[0]} not found");
                }
                //TODO check method exists
            } else {
                if (!function_exists($callback)) {
                    throw new waException("Function {$callback} not found");
                }
            }
            return call_user_func_array($callback, array($name, $params));
        }
        return null;
    }

    /**
     *
     * @param array $list
     * @param array $params
     * @return string
     */
    private function addCustomParams($list, $params = array())
    {
        $params_string = '';
        foreach ($list as $param => $target) {
            if (is_int($param)) {
                $param = $target;
            }
            if (isset($params[$param])) {
                $param_value = $params[$param];
                if (is_array($param_value)) {
                    if (array_filter($param_value, 'is_array')) {
                        $param_value = json_encode($param_value);
                    } else {
                        $param_value = implode(' ', $param_value);
                    }
                }
                if ($param_value !== false) {
                    if (in_array($param, array('title', 'description', 'placeholder'))) {
                        $param_value = self::_wp($param_value, $params);
                    } elseif (in_array($param, array('disabled', 'readonly'))) {
                        $param_value = $param;
                    }
                    $param_value = self::escape($param_value);
                    if (in_array($param, array('autofocus'))) {
                        $params_string .= " {$target}";
                    } else {
                        $params_string .= " {$target}=\"{$param_value}\"";
                    }
                }
            }
        }
        if (!empty($params['data'])) {
            $data = array();
            foreach ($params['data'] as $field => $value) {
                $data['data-'.$field] = trim(json_encode($value), '"');
            }
            $params_string .= $this->addCustomParams(array_keys($data), $data);
        }
        return $params_string;
    }

    protected static function escape($string, $quote_style = ENT_QUOTES)
    {
        return htmlentities((string)$string, $quote_style, self::$default_charset);
    }


    private static function _wp($param, $params = array())
    {
        $translate = (!empty($params['translate']) && is_callable($params['translate'])) ? $params['translate'] : '_wp';
        if (is_array($param)) {
            if (!isset($params['translate']) || !empty($params['translate'])) {
                $param[key($param)] = call_user_func($translate, current($param));
            }
            $string = call_user_func_array('sprintf', $param);
        } elseif (!isset($params['translate']) || !empty($params['translate'])) {
            $string = call_user_func($translate, $param);
        } else {
            $string = $param;
        }
        return $string;
    }
}
