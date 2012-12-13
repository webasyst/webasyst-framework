<?php
/**
 *
 * @author WebAsyst Team
 * @version SVN: $Id: class.htmlcontrol.php 1552 2010-10-19 16:36:58Z vlad $
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
    const CUSTOM = 'custom';
    const HIDDEN = 'hidden';

    static private $predefined_controls = array();
    static private $custom_controls = array();
    static private $instance = null;

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
        );
    }

    static $default_charset = 'utf-8';

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
     * @param array[string]mixed $params Control params
     * @param array[string]mixed $params['namespace'] array of string or string with control namespace
     * @param array[string]mixed $params['class'] array of string or string with control CSS class
     * @param array[string]mixed $params['style'] HTML property of control
     * @param array[string]mixed $params['value']
     * @param array[string]mixed $params['size'] HTML property of control
     * @param array[string]mixed $params['maxlength'] HTML property of control
     * @param array[string]mixed $params['cols'] HTML property of control
     * @param array[string]mixed $params['rows'] HTML property of control
     * @param array[string]mixed $params['wrap']
     * @param array[string]mixed $params['callback']
     * @param array[string]array $params['options'] variants for selectable control
     * @param array[string][][string]string $params['options']['title'] variant item title for selectable control
     * @param array[string][][string]mixed $params['options']['value'] variant item value for selectable control
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
                    {
                        if (preg_match('/^([\w:]+)(.*)$/', $options, $matches)) {
                            $params['callback'] = $matches[1];
                            if (preg_match('/^[\w]+::[\w]+$/', $params['callback'])) {
                                $params['callback'] = explode('::', $params['callback']);
                            }
                            $options = trim($matches[2]);
                        }
                        break;
                    }
            }

            #transform raw options data into array
            if (!isset($params['options'])) {
                $params['options'] = self::getControlParams($options);
            }
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
        if (isset($params['namespace'])) {
            if (is_array($params['namespace'])) {
                $namespace = array_shift($params['namespace']);
                while (($namspace_chunk = array_shift($params['namespace'])) !== null) {
                    $namespace .= "[{$namspace_chunk}]";
                }
            } else {
                $namespace = $params['namespace'];
            }

            $name = "{$namespace}[{$name}]";
            unset($params['namespace']);
        }
        #usage dual standart for options value=>title
        if (isset($params['options']) && is_array($params['options'])) {
            foreach ($params['options'] as $key => & $data) {
                if (!is_array($data)) {
                    //TODO check format usage
                    $data = array('title' => $data, 'value' => $key);
                }
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
        $wrappers = array(
            'title_wrapper'       => '%s&nbsp;:',
            'description_wrapper' => '<br>%s<br>',
            'control_wrapper'     => "%s\n%s\n%s\n",
            'control_separator'   => "<br>",

        );
        $params = array_merge($wrappers, $params);
        $instance = self::getInstance();
        self::makeId($params, $name);
        $control = $instance->$control_name($name, $params);
        $res = sprintf($params['control_wrapper'], self::getControlTitle($params), $control, self::getControlDescription($params));
        return $res;
    }

    /**
     * @param $raw_params string raw control params in CSV format
     * @return array
     */
    private static function getControlParams($raw_params)
    {
        $options = null;
        if (preg_match('/^([_\w]+)::([_\w]+)(\s+.+)?$/', $raw_params, $matches) && class_exists($matches[1]) && in_array($matches[2], get_class_methods($matches[1]))) {
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
                $type = strtolower($type);
                return "Control type <b>{$type}</b> undefined";
            }
        } else {
            throw new Exception("Call undefined function {$function_name} at ".__CLASS__);
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
            throw new Exception("invalid callback for control type {$type}");
        }
    }

    /**
     *
     * @param $params
     * @param $name
     * @param $id
     * @return string
     */
    public static final function makeId(&$params, $name = '', $id = null)
    {
        //settings_{$name}_{$id}
        $params['id'] = $id ? $id : ((isset($params['id']) && $params['id']) ? $params['id'] : strtolower(__CLASS__));
        if (isset($params['namespace'])) {

        }
        if ($name) {
            $params['id'] .= "_{$name}";
        }
        $params['id'] = preg_replace(array('/[_]{2,}/', '/[_]{1,}$/'), array('_', ''), str_replace(array('[', ']'), '_', $params['id']));
    }

    /**
     * Add namespace for control params
     * name="control_name" became name="namespace[control_name]" for string and name="namespace1[namespace2]...[control_name ] for array
     * @param $params array
     * @param $namespace string|array
     * @return void
     */
    public static final function addNamespace(&$params, $namespace = '')
    {
        if (isset($params['namespace'])) {
            if (!is_array($params['namespace'])) {
                $params['namespace'] = array($params['namespace']);
            }
        } else {
            $params['namespace'] = array();
        }
        foreach ((array) $namespace as $chunk) {
            $params['namespace'][] = $chunk;
        }
    }

    private static function getControlTitle($params)
    {
        $title = '';
        if (isset($params['title']) && $params['title_wrapper']) {
            $option_title = htmlentities(self::_wp($params['title']), ENT_QUOTES, self::$default_charset);
            $title = sprintf($params['title_wrapper'], "<label for=\"{$params['id']}\">{$option_title}</label>\n");
        }
        return $title;
    }

    private static function getControlDescription($params)
    {
        $description = '';
        if (!empty($params['description_wrapper']) && !empty($params['description'])) {
            $description = sprintf($params['description_wrapper'], self::_wp($params['description']));
        }
        return $description;
    }

    private function getInputControl($name, $params = array())
    {
        $control = '';
        $control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
        $control .= "<input id=\"{$params['id']}\" type=\"text\" name=\"{$name}\" ";
        $control .= self::addCustomParams(array('class', 'style', 'size', 'maxlength', 'value'), $params);
        $control .= ">";
        return $control;
    }

    private function getHiddenControl($name, $params = array())
    {
        $control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
        $control = "<input type=\"hidden\" name=\"{$name}\" ";
        $control .= self::addCustomParams(array('class', 'value'), $params);
        $control .= ">";
        return $control;
    }

    private function getFileControl($name, $params = array())
    {
        $control = '';
        $control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
        $control .= "<input type=\"file\" name=\"{$name}\" ";
        $control .= self::addCustomParams(array('class', 'style', 'size', 'maxlength', 'value', 'id'), $params);
        $control .= ">";
        return $control;
    }

    private function getTextareaControl($name, $params = array())
    {
        $control = '';
        $control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
        $value = htmlentities((string) $params['value'], ENT_QUOTES, self::$default_charset);
        $control .= "<textarea name=\"{$name}\"";
        $control .= self::addCustomParams(array('class', 'style', 'cols', 'rows', 'wrap', 'id', ), $params);
        $control .= ">{$value}</textarea>";
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
        $control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
        $control .= "<input type=\"password\" name=\"{$name}\"";
        $control .= self::addCustomParams(array('class', 'style', 'size', 'maxlength', 'value', 'id'), $params);
        $control .= ">";
        return $control;
    }

    private function getRadiogroupControl($name, $params = array())
    {

        $control = '';
        $id = 0;
        $value = htmlentities((string) $params['value'], ENT_QUOTES, self::$default_charset);
        $options = isset($params['options']) ? (is_array($params['options']) ? $params['options'] : array($params['options'])) : array();
        foreach ($options as $option) {
            ++$id;
            $option_value = $option['value'];
            if ($option_value == $value) {
                $params['checked'] = 'checked';
            } elseif (isset($params['checked'])) {
                unset($params['checked']);
            }
            self::makeId($params, $name, $id);
            $option_value = htmlentities((string) $option_value, ENT_QUOTES, self::$default_charset);
            $control .= "<input type=\"radio\" name=\"{$name}\" value=\"{$option_value}\"";
            $control .= self::addCustomParams(array('class', 'style', 'id', 'checked'), $params);
            $option_title = htmlentities(self::_wp($option['title']), ENT_QUOTES, self::$default_charset);
            $control .= ">&nbsp;<label";
            $control .= self::addCustomParams(array('id' => 'for', ), $params);
            $control .= self::addCustomParams(array('description' => 'title', 'class', 'style', ), $option);
            $control .= ">{$option_title}</label>\n";

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
        $control .= "<select name=\"{$name}\"";
        $control .= self::addCustomParams(array('class', 'style', 'id'), $params);
        $control .= ">\n";
        $groupbox = null;
        foreach ($options as $option) {
            if ($groupbox && (empty($option['group']) || ($option['group'] != $groupbox))) {
                $groupbox = false;
                $control .= "\n</optgroup>\n";
            }
            if (!empty($option['group']) && ($option['group'] != $groupbox)) {
                $groupbox = htmlentities((string) $option['group'], ENT_QUOTES, self::$default_charset);
                $control .= "\n<optgroup label=\"{$groupbox}\">\n";
            }

            ++$id;
            $option_value = $option['value'];
            if (isset($params['value']) && ($option_value == $params['value'])) {
                $params['selected'] = 'selected';
            } elseif (isset($params['selected'])) {
                unset($params['selected']);
            }
            if (isset($option['description'])) {
                $params['description'] = $option['description'];
            }
            $option_value = htmlentities((string) $option_value, ENT_QUOTES, self::$default_charset);
            $control .= "<option value=\"{$option_value}\"";
            $control .= self::addCustomParams(array('selected'), $params);
            $control .= self::addCustomParams(array('class', 'style', 'description' => 'title', ), $option);
            $option_title = htmlentities(self::_wp($option['title']), ENT_QUOTES, self::$default_charset);
            $control .= ">{$option_title}</option>\n";
        }
        if ($groupbox) {
            $control .= "\n</optgroup>\n";
        }
        $control .= "</select>";
        return $control;
    }

    private function getGroupboxControl($name, $params = array())
    {
        $control = '';
        $options = isset($params['options']) ? (is_array($params['options']) ? $params['options'] : array($params['options'])) : array();
        $checked = isset($params['checked']) ? (is_array($params['checked']) ? $params['checked'] : array($params['checked'])) : array();
        if (!is_array($params['value'])) {
            $params['value'] = array();
        }
        self::addNamespace($params, $name);
        $checkbox_params = $params;
        if (isset($params['options'])) {
            unset($checkbox_params['options']);
        }
        $values = $params['value'];
        foreach ($options as $option) {
            //TODO check that $option is array
            $checkbox_params['value'] = isset($values[$option['value']]) ? $values[$option['value']] : null;
            $checkbox_params['title'] = $option['title'];
            if ($checked) {
                $checkbox_params['checked'] = isset($checked[$option['value']]) ? $checked[$option['value']] : null;
            }
            $control .= self::getControl(self::CHECKBOX, $option['value'], $checkbox_params);
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
            )
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
        if (!isset($params['value'])) {
            $params['value'] = 1;
        }
        if (isset($params['label']) && $params['label']) {
            $control .= "<label";
            $control .= self::addCustomParams(array('for' => 'id'), $params);
            $control .= ">";
        }
        $control .= "<input type=\"checkbox\" name=\"{$name}\"";
        $control .= self::addCustomParams(array('value', 'class', 'style', 'checked', 'id', 'title'), $params);
        $control .= ">";
        if (isset($params['label']) && $params['label']) {
            $control .= '&nbsp;'.htmlentities(self::_wp($params['label']), ENT_QUOTES, self::$default_charset)."</label>";
        }

        return $control;
    }

    /**
     * @todo complete params check
     * @param $name
     * @param $params
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
                if (!class_exists($callback[0])) {
                    throw new Exception("Class {$callback[0]} not found");
                }
                //TODO check method exists
                } else {
                if (!function_exists($callback)) {
                    throw new Exception("Function {$callback} not found");
                }
            }
            return call_user_func_array($callback, array($name, $params));
        }
        return null;
    }

    /**
     *
     * @param array $params_list
     * @param array $params_values
     * @return string
     */
    private function addCustomParams($params_list, $params_values = array())
    {
        $params_string = '';
        foreach ($params_list as $param => $target) {
            if (is_int($param)) {
                $param = $target;
            }
            if (isset($params_values[$param])) {
                $param_value = $params_values[$param];
                if (is_array($param_value)) {
                    $param_value = implode(' ', $param_value);
                }
                if ($param_value !== false) {
                    if (in_array($param, array('title', 'description'))) {
                        $param_value = self::_wp($param_value);
                    }
                    $param_value = htmlentities((string) $param_value, ENT_QUOTES, self::$default_charset);
                    $params_string .= " {$target}=\"{$param_value}\"";
                }
            }
        }
        return $params_string;
    }

    private static function _wp($param)
    {
        if (is_array($param)) {
            $param[key($param)] = _wp(current($param));
            $string = call_user_func_array('sprintf', $param);
        } else {
            $string = _wp($param);
        }
        return $string;
    }

}
