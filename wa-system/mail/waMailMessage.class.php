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
 * @subpackage mail
 */
class waMailMessage
{
	protected $headers = array(
		'Content-type' => 'text/html; charset=UTF-8',
		'Content-Transfer-Encoding' => 'base64'
	);
	protected $subject;
	protected $body;
	
	public function __construct($to = null, $subject = null, $body = null, $from = null)
	{
		$this->addTo($to);
		$this->setSubject($subject);
		$this->setBody($body);
		if ($from === null) {
			$app_settings_model = new waAppSettingsModel();
			$name = $app_settings_model->get('webasyst', 'name');
			$from = $app_settings_model->get('webasyst', 'email');
			if ($name) {
				$from = $name.' <'.$from.'>';
			}
		}
		if ($from) {
			$this->setFrom($from);
		}
	}
	
	protected function addHeader($name, $value)
	{
		if ($value) {
			$this->headers[$name][] = $value;
		}
	}
	
	public function setHeader($name, $value)
	{
		if ($value) {
			$this->headers[$name] = $value;
		}
	}
	
	public function setSubject($subject)
	{
		$this->subject = $subject;
	}
	
	public function getSubject($encode = false)
	{
		if ($encode) {
			return $this->encodeHeader($this->subject);
		} else {
			return $this->subject;
		}
	}
	
	public function setBody($body)
	{
		$this->body = $body;
	}
	
	public function setFrom($from)
	{
		$this->setHeader('From', $from);
		$this->setHeader('Reply-to', $from);
		
	}
	
	public function addTo($to)
	{
		$this->addHeader('To', $to);
	}
	
	public function getTo($encode = false)
	{
		$to = $this->getHeaders('To');
		if ($encode) {
			foreach ($to as &$str) {
				$str = $this->encodeHeader($str);
			}
			return implode(",", $to);
		} else {
			return $to;
		}
	}
	
	public function getHeaders($name = null)
	{
		if ($name === null) {
			return $this->headers;
		} elseif ($name === true) {
			// return all encoded headers
		    $encode_headers = array('From', 'To', 'Cc', 'Bcc', 'Reply-to');
			$headers = array();
			foreach ($this->headers as $name => $value) {
				if ($name === 'To') continue;
				if (is_array($value)) {
					foreach ($value as $v) {
						if (in_array($name, $encode_headers)) {
							$headers[] = $name.": ".$this->encodeHeader($v);
						} else {
							$headers[] = $name.": ".$v;
						}
					}
				} else {
					if (in_array($name, $encode_headers)) {
						$headers[] = $name.": ".$this->encodeHeader($value);	
					} else {
						$headers[] = $name.": ".$value;
					}
					
				}
			}		
			return implode("\r\n", $headers);	
		}
		return isset($this->headers[$name]) ? $this->headers[$name] : null;
	}
	
	public function getFrom($encode = true)
	{
		$from = $this->getHeaders('From');
		if ($encode) {
			return $this->encodeHeader($from);
		}
		return $from;
	}
	
	public function getBody($encode = false)
	{
		if ($encode) {
			return rtrim(chunk_split(base64_encode($this->body), 76, "\r\n"));
		} else {
			return $this->body;
		}
	}
	
	public function encodeHeader($value)
	{
		$value = trim($value);
		if (preg_match('/^<\S+@\S+>$/', $value)) {
			return $value;
        } else if (preg_match('/^\S+@\S+$/', $value)) {
            // address without brackets and without name
			return $value;
		} else if (preg_match('/<*\S+@\S+>*$/', $value, $matches)) {
            // address with name (handle name)
            $address = $matches[0];
            $word = str_replace($address, '', $value);
            $word = trim($word);
            // check if phrase requires quoting
            if ($word) {
	            // non-ASCII: require encoding
                if (preg_match('#([\x80-\xFF]){1}#', $word)) {
	                if ($word[0] == '"' && $word[strlen($word)-1] == '"') {
	                	// de-quote quoted-string, encoding changes
	                    // string to atom
	                    $search = array("\\\"", "\\\\");
	                    $replace = array("\"", "\\");
	                    $word = str_replace($search, $replace, $word);
	                    $word = substr($word, 1, -1);
	                }
	                $word = mb_encode_mimeheader($word, 'UTF-8', 'B');
                } else if (($word[0] != '"' || $word[strlen($word)-1] != '"')
                            && preg_match('/[\(\)\<\>\\\.\[\]@,;:"]/', $word)
                ){
                    // ASCII: quote string if needed
                	$word = '"'.addcslashes($word, '\\"').'"';
                }
                
                $address = preg_replace('/^<*(\S+@\S+?)>*$/', '<$1>', $address);
                return $word.' '.$address;
            }
            return $address;
        } else {
        	// addr-spec not found, don't encode (?)
            return mb_encode_mimeheader($value, 'UTF-8', 'B');
        }	
	}
}