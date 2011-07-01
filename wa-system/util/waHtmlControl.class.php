<?php
/**
 *
 * @author WebAsyst Team
 * @version SVN: $Id: class.htmlcontrol.php 1552 2010-10-19 16:36:58Z vlad $
 *
 */
class waHtmlControl
{
    const INPUT		 = 'input';
    const FILE		 = 'file';
    const TEXTAREA	 = 'textarea';
    const PASSWORD	 = 'password';
    const RADIOGROUP = 'radiogroup';
    const SELECT	 = 'select';
    const CHECKBOX	 = 'checkbox';
    const GROUPBOX	 = 'groupbox';
    const INTERVAL	 = 'interval';
    const CUSTOM	 = 'custom';
    const HIDDEN	 = 'hidden';

    static private $predefined_controls = array();
    static private $custom_controls = array();
    static private $instance = null;

    private function __construct() {
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

    private function __clone() {}

    /**
     *
     * @return waHtmlControl
     */
    private static function getInstance()
    {
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get control html code
     *
     * <pre>$params=array(
     * 	'namespace'=>string/array of string,
     * 	'class'=>string/array of string,
     * 	'value'=>mixed,
     * 	'size'=>int,
     * 	'maxlength'=>int,
     * 	'cols'=>int,
     * 	'rows'=>int,
     * 	'wrap'=>bool,
     * 	'options'=>array(
     * 		array('value'=>mixed,'title'=>string),
     * 		...
     * 		)
     * )
     * </pre>
     * @throws Exception
     * @param string $type Type of control (use standard or try to found registered control types) (also support raw control type)
     * @param string $name
     * @param array $params
     * @return string
     */
    public static function getControl($type,$name,$params = array())
    {
        if(!is_array($params)){
            throw new waException('Invalid function params at '.__METHOD__);
        }
        #raw type support
        if(preg_match('/^([\w]+)(\s+.*)$/',$type,$matches)){
            $type	= $matches[1];
            $options = trim($matches[2]);
            switch($type){
                case self::CUSTOM:{
                    if(preg_match('/^([\w:]+)(.*)$/',$options,$matches)){
                        $params['callback'] = $matches[1];
                        if(preg_match('/^[\w]+::[\w]+$/',$params['callback'])){
                            $params['callback'] = explode('::',$params['callback']);
                        }
                        $options = trim($matches[2]);
                    }
                    break;
                }
            }

            #transform raw options data into array
            if(!isset($params['options'])){
                $params['options']=self::getControlParams($options);
            }
        }

        #input exception handler support
        //TODO check and complete code
        if(isset($params['wrong_value'])){
            if(false&&isset($params['value'])&&is_array($params['value'])&&is_array($params['wrong_value'])){
                $params['value']=array_merge($params['value'],$params['wrong_value']);
            }else{
                $params['value']=$params['wrong_value'];
            }
        }

        #namespace workaround
        if(isset($params['name'])){
            $name = $name?$name:$params['name'];
            unset($params['name']);
        }
        if(isset($params['namespace'])){
            if(is_array($params['namespace'])){
                $namespace = array_shift($params['namespace']);
                while(($namspace_chunk = array_shift($params['namespace']))!==null){
                    $namespace .= "[{$namspace_chunk}]";
                }
            }else{
                $namespace = $params['namespace'];
            }

            $name = "{$namespace}[{$name}]";
            unset($params['namespace']);
        }
        #usage dual standart for options value=>title
        if(isset($params['options'])&&is_array($params['options'])){
            foreach($params['options'] as $key=>&$data){
                if(!is_array($data)){
                    //TODO check format usage
                    $data = array('title'=>$data,'value'=>$key);
                }
            }
        }

        #type aliases
        switch($type){
            case 'text':$type=self::INPUT;break;
            default:break;
        }

        $control_name = "get".ucfirst($type)."Control";
        if(!isset($params['class'])){
            $params['class'] = array();
        }elseif(!is_array($params['class'])){
            $params['class'] = array($params['class']);
        }

        $params['class'][] = $type;
        $instance = self::getInstance();
        return $instance->$control_name($name,$params);
    }



    /**
     * @param $raw_params string raw control params in CSV format
     * @return array
     */
    private static function getControlParams($raw_params)
    {
        $options = null;
        if(preg_match('/^([_\w]+)::([_\w]+)$/',$raw_params,$matches)&&class_exists($matches[1])&&in_array($matches[2],get_class_methods($matches[1]))){
            $callback = array($matches[1],$matches[2]);
            $options = call_user_func($callback);
        }elseif(preg_match('/([_\w]+)(.*)/',$raw_params,$matches)&&function_exists($matches[1])){
            $options = call_user_func($matches[1],$matches[2]);
        }

        #parse CSV format
        if(!is_array($options)&&$raw_params){
            $csv_pattern = '@(?:,|^)([^",]+|"(?:[^"]|"")*")?@';
            $cell_pattern = '@^"([^"].+[^"])"$@';
            if(preg_match_all($csv_pattern,$raw_params,$matches)){
                $options = array();
                foreach($matches[1] as $param){
                    if(preg_match($cell_pattern,$param,$param_matches)){
                        $param = $param_matches[1];
                    }
                    $param = str_replace('""','"',$param);
                    $param = explode(':',$param,2);
                    $options[] = array('title'=>$param[0],'value'=>isset($param[1])?$param[1]:$param[0]);
                }
            }
        }
        return $options;
    }

    public function __call($function_name,$args = null)
    {
        if(preg_match('/^get(\w+)Control$/',$function_name,$matches)){
            $type = $matches[1];
            $name = array_shift($args);
            $params = array_shift($args);
            if(!isset($params['value'])){
                $params['value'] = isset($params['default'])?$params['default']:false;
            }
            if(isset(self::$custom_controls[$type])){
                return call_user_func_array(self::$custom_controls[$type],array($name,$params));
            }else{
                throw new Exception("Control type {$type} undefined");
            }
        }else{
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
    public static function registerControl($type,$callback)
    {
        if(is_callable($callback)){
            self::$custom_controls[$type] = $callback;
        }else{
            throw new Exception("invalid callback for control type {$type}");
        }
    }

    public static final function makeId(&$params,$name = '',$id=null)
    {
        //settings_{$name}_{$id}
        $params['id'] = $id?$id:strtolower(__CLASS__);
        if(isset($params['namespace'])){

        }
        if($name){
            $params['id'] .= "_{$name}";
        }
        $params['id'] = preg_replace(array('/[_]{2,}/','/[_]{1,}$/'),array('_',''),str_replace(array('[',']'),'_',$params['id']));
    }

    public static final function addNamespace(&$params,$name = '')
    {
        if(isset($params['namespace'])){
            if(!is_array($params['namespace'])){
                $params['namespace'] = array($params['namespace']);
            }
        }else{
            $params['namespace'] = array();
        }
        $params['namespace'][] = $name;
    }

    private function getInputControl($name,$params = array())
    {
        $control = '';
        self::makeId($params,$name);
        $control_name = htmlentities($name,ENT_QUOTES,self::$default_charset);
        if(isset($params['title'])){
            $option_title = htmlentities(_w((string)$params['title']),ENT_QUOTES,self::$default_charset);
            $control .= "<label for=\"{$params['id']}\">{$option_title}</label>:&nbsp;\n";
        }
        $control .= "<input id=\"{$params['id']}\" type=\"text\" name=\"{$name}\" ";
        $control .= self::addCustomParams(array('class','style','size','maxlength','value'),$params);
        $control .= ">";
        return $control;
    }

    private function getHiddenControl($name,$params = array())
    {
        $control_name = htmlentities($name,ENT_QUOTES,self::$default_charset);
        $control = "<input type=\"hidden\" name=\"{$name}\" ";
        $control .= self::addCustomParams(array('class','value'),$params);
        $control .= ">";
        return $control;
    }

    private function getFileControl($name,$params = array())
    {
        $control_name = htmlentities($name,ENT_QUOTES,self::$default_charset);
        $control = "<input type=\"file\" name=\"{$name}\" ";
        $control .= self::addCustomParams(array('class','style','size','maxlength','value'),$params);
        $control .= ">";
        return $control;
    }

    private function getTextareaControl($name,$params = array())
    {
        $control_name = htmlentities($name,ENT_QUOTES,self::$default_charset);
        $value = htmlentities((string)$params['value'],ENT_QUOTES,self::$default_charset);
        $control = "<textarea name=\"{$name}\"";
        $control .= self::addCustomParams(array('class','style','cols','rows','wrap',),$params);
        $control .= ">{$value}</textarea>";
        return $control;
    }

    /**
     * @todo hide real value for password
     * @param $name
     * @param $params
     * @return unknown_type
     */
    private function getPasswordControl($name,$params = array())
    {
        $control_name = htmlentities($name,ENT_QUOTES,self::$default_charset);
        $control = "<input type=\"password\" name=\"{$name}\"";
        $control .= self::addCustomParams(array('class','style','size','maxlength', 'value'),$params);
        $control .= ">";
        return $control;
    }

    private function getRadiogroupControl($name,$params = array())
    {
        $control = '';
        $id = 0;
        $value = htmlentities((string)$params['value'],ENT_QUOTES,self::$default_charset);
        $options = isset($params['options'])?(is_array($params['options'])?$params['options']:array($params['options'])):array();
        foreach($options as $option){
            ++$id;
            $option_value = $option['value'];
            if($option_value == $value){
                $params['checked'] = 'checked';
            }elseif(isset($params['checked'])){
                unset($params['checked']);
            }
            self::makeId($params,$name,$id);
            $option_value = htmlentities((string)$option_value,ENT_QUOTES,self::$default_charset);
            $control .= "<input type=\"radio\" id=\"{$params['id']}\" name=\"{$name}\" value=\"{$option_value}\"";
            $control .= self::addCustomParams(array('class','style','id','checked'),$params);
            $option_title = htmlentities((string)$option['title'],ENT_QUOTES,self::$default_charset);
            $control .= "><label for=\"{$params['id']}\">{$option_title}</label><br>\n";
        }
        return $control;
    }

    private function getSelectControl($name,$params = array())
    {
        $control = '';
        $id = 0;
        $options = isset($params['options'])?(is_array($params['options'])?$params['options']:array($params['options'])):array();
        $control = "<select name=\"{$name}\"";
        $control .= self::addCustomParams(array('class','style'),$params);
        $control .= ">\n";
        foreach($options as $option){
            ++$id;
            $option_value = $option['value'];
            if(isset($params['value'])&&($option_value == $params['value'])){
                $params['selected'] = 'selected';
            }elseif(isset($params['selected'])){
                unset($params['selected']);
            }
            $option_value = htmlentities((string)$option_value,ENT_QUOTES,self::$default_charset);
            $control .= "<option value=\"{$option_value}\"";
            $control .= self::addCustomParams(array('selected'),$params);
            $control .= self::addCustomParams(array('class','style'),$option);
            $option_title = htmlentities(_w((string)$option['title']),ENT_QUOTES,self::$default_charset);
            $control .= ">{$option_title}</option>\n";
        }
        $control .= "</select>";
        return $control;
    }

    private function getGroupboxControl($name,$params = array())
    {
        $control = '';
        $options = isset($params['options'])?(is_array($params['options'])?$params['options']:array($params['options'])):array();
        $checked = isset($params['checked'])?(is_array($params['checked'])?$params['checked']:array($params['checked'])):array();
        if(!is_array($params['value'])){
            $params['value'] = array();
        }
        self::addNamespace($params,$name);
        $checkbox_params = $params;
        if(isset($params['options'])){
            unset($checkbox_params['options']);
        }
        $values = $params['value'];
        foreach($options as $option){
            //TODO check that $option is array
            $checkbox_params['value'] = isset($values[$option['value']])?$values[$option['value']]:null;
            $checkbox_params['title'] = $option['title'];
            if($checked){
                $checkbox_params['checked'] = isset($checked[$option['value']])?$checked[$option['value']]:null;
            }
            $control .= self::getControl(self::CHECKBOX,$option['value'],$checkbox_params)."<br>\n";
        }
        return $control;
    }

    private function getIntervalControl($name,$params = array())
    {
        $control = '';
        if(!is_array($params['value'])){
            $params['value'] = array();
        }
        $input_params = $params;
        self::addNamespace($input_params,$name);
        $input_name = "from";
        $input_params['value'] = $params['value']['from'];
        $input_params['title'] = 'str_from';

        $control .= self::getControl(self::INPUT,$input_name,$input_params)."\n";

        $input_params = $params;
        $input_name = "to";
        self::addNamespace($input_params,$name);
        $input_params['value'] = $params['value']['to'];
        $input_params['title'] = 'str_to';
        $control .= self::getControl(self::INPUT,$input_name,$input_params)."\n";
        return $control;
    }

    private function getCheckboxControl($name,$params = array())
    {
        $value = isset($params['value'])?$params['value']:false;
        if($value){
            if(!isset($params['checked'])){
                $params['checked'] = 'checked';
            }
        }elseif(isset($params['checked'])){
            unset($params['checked']);
        }
        self::makeId($params,$name);
        if(!isset($params['value'])){
            $params['value'] = 1;
        }
        $control = "<input type=\"checkbox\" name=\"{$name}\"";
        $control .= self::addCustomParams(array('value','class','style','checked','id'),$params);
        $control .= ">";
        if(isset($params['title'])){
            $control .= "\n<label";
            $control .= self::addCustomParams(array('class','style','id'=>'for'),$params);
            $control .= ">{$params['title']}</label>";
        }
        return $control;
    }

    /**
     * @todo complete params check
     * @param $name
     * @param $params
     * @return string
     */
    private function getCustomControl($name,$params = array())
    {
        $callback = isset($params['callback'])?$params['callback']:null;
        if($callback){
            unset($params['callback']);
            if(is_array($callback)){
                if(!class_exists($callback[0])){
                    throw new Exception("Class {$callback[0]} not found");
                }
                //TODO check method exists
            }else{
                if(!function_exists($callback)){
                    throw new Exception("Function {$callback} not found");
                }
            }
            return  call_user_func_array($callback,array($name,$params));
        }
    }

    /**
     *
     * @param array $params_list
     * @param array $params_values
     * @return string
     */
    private function addCustomParams($params_list,$params_values = array()){
        $params_string = '';
        foreach($params_list as $param=>$target){
            if(is_int($param)){
                $param = $target;
            }
            if(isset($params_values[$param])){
                $param_value = $params_values[$param];
                if(is_array($param_value)){
                    $param_value = implode(' ',$param_value);
                }
                if($param_value !== false){
                    $param_value = htmlentities((string)$param_value,ENT_QUOTES,self::$default_charset);
                    $params_string .= " {$target}=\"{$param_value}\"";
                }
            }
        }
        return $params_string;
    }

}
