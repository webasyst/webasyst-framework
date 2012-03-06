<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage locale
 */
class waGettext
{
	protected $type;
	protected $file;
	protected $meta;
	protected $f;
	protected $messages;
	
	public function __construct($file)
	{
		$this->file = $file;
        $path_info = pathinfo($this->file);
        $this->type = $path_info['extension'];		
	}
	
	public function read()
	{
		if ($this->type == 'mo') {
			return $this->readMo();
		} else {
			return $this->readPo();
		}	
	}
	
	protected function readPo()
	{
		$file = $this->file;
	    if (!$contents = @file($file)) {
            return false;
        }
        $contents = implode('', $contents);
        
        // match all msgid/msgstr entries
        $matched = preg_match_all(
            '/(msgid\s+(?:"(?:\\\\"|[^"])*?"\s*)+)\s+' .
        	'(msgid_plural\s+(?:"(?:[^"]|\\\\")*?"\s*)+)?\s*'.
            '((?:msgstr(?:\[\d\])?\s+(?:"(?:\\\\"|[^"])*"\s*)+\s+)+)/u',
            $contents, $matches
        );
        
        
        unset($contents);
        
        if (!$matched) {
            return false;
        }
        
        // get all msgids and msgtrs
        for ($i = 0; $i < $matched; $i++) {
            $msgid = preg_replace('/\s*msgid\s*"(.*)"\s*/s', '\\1', $matches[1][$i]);
            
            $msgid_plural = preg_replace('/\s*msgid_plural\s*"(.*)"\s*/s', '\\1', $matches[2][$i]);
            
            $msgstr= preg_replace('/\s*msgstr\s*"(.*)"\s*/s', '\\1', $matches[3][$i]);
                        
            if ($msgid_plural) {
            	$msgstr = preg_replace('/\s*msgstr\[\d\]\s*"((?:\\\\"|[^"])*)"\s*/si', "\\1\n\n", $matches[3][$i]);
            	$msgstr = explode("\n\n", rtrim($msgstr, "\n"));
            }
            
            if (is_array($msgstr)) {
            	foreach ($msgstr as &$m) {
            		$m = $this->prepare($m);
            	} 
            } else {
            	$msgstr = $this->prepare($msgstr);
            }
            
            // ignore strings without translation
            if ($msgstr === "") {
                continue;
            }

            $this->messages[$this->prepare($msgid)] = $msgstr;
        }		
        
		if (isset($this->messages[''])) {
            $this->meta = $this->meta2array($this->messages['']);
            unset($this->messages['']);
        }
        
        return array(
        	'meta' => $this->meta,
        	'messages' => $this->messages,
        	'f' => $this->f
        );
	}

    protected function readMo()
    {

    }
	
    protected function prepare($string, $reverse = false)
    {
        if ($reverse) {
            $smap = array('"', "\n", "\t", "\r");
            $rmap = array('\"', '\\n"' . "\n" . '"', '\\t', '\\r');
            return (string) str_replace($smap, $rmap, $string);
        } else {
        	$string = preg_replace('/"\s+"/', '', $string);
            $smap = array('\\n', '\\r', '\\t', '\"');
            $rmap = array("\n", "\r", "\t", '"');
            return (string) str_replace($smap, $rmap, $string);
        }
    }	
    
    function meta2array($meta)
    {
        $array = array();
        foreach (explode("\n", $meta) as $info) {
            if ($info = trim($info)) {
                list($key, $value) = explode(':', $info, 2);
                $array[trim($key)] = trim($value);
            }
        }
        if (isset($array['Plural-Forms'])) {
        	$data  = explode(";", $array['Plural-Forms']);
        	$array['Plural-Forms'] = array();
        	foreach ($data as $s) {
        		if (trim($s)) {
	        		$s = explode("=", trim($s), 2);
	        		$array['Plural-Forms'][$s[0]] = $s[1];
        		}
        	}
        	$array['Plural-Forms']['plural'] = 'return '.str_replace('n', '$n', $array['Plural-Forms']['plural']).';';
        }
        
        return $array;
    }    
}
