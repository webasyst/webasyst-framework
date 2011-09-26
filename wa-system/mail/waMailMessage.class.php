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
		'Content-Transfer-Encoding' => 'base64',
		'MIME-Version' => '1.0'
	);
	
	protected $subject;
	protected $body;
	
	protected $content_type = 'text/html';
	
	protected $boundary;
	protected $attachments = array();
	
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
	
	/**
	 * Set Content-Type of the mail message
	 * 
	 * @param string $type - html or plain
	 */
	public function setContentType($type)
	{
		switch (strtolower($type)) {
			case 'html':
				$this->content_type = 'text/html';
				break;
			case 'text':
			case 'plain':
				$this->content_type = 'text/plain';
				break;
			default:
				throw new waException("Unknown Content-Type ".$type);
		}
	}
	
	protected function addHeader($name, $value)
	{
		if ($value) {
			if (isset($this->headers[$name])) {
				if (!is_array($this->headers[$name])) {
					$this->headers[$name] = array($this->headers[$name]);
				}
			} else {
				$this->headers[$name] = array();
			}
			if (is_array($value)) {
				$this->headers[$name] = array_merge($this->headers[$name], $value);
			} else {
				$this->headers[$name][] = $value;
			}
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
	}
	
	public function setReplyTo($reply_to)
	{
		$this->setHeader('Reply-To', $reply_to);
	}
	
	/** 
	 * @param string|array $to
	 */
	public function addTo($to)
	{
		$this->addHeader('To', $to);
	}
	
	
	public function setTo($to)
	{
	    $this->setHeader('To', is_array($to) ? $to : array($to));
	}

	/** 
	 * @param string|array $cc
	 */
	public function addCc($cc)
	{
		$this->addHeader('Cc', $cc);
	}	
	
	/** 
	 * @param string|array $bcc
	 */			
	public function addBcc($bcc)
	{
		$this->addHeader('Bcc', $bcc);
	}	
	
	public function getTo($encode = false)
	{
		$to = $this->getHeader('To');
		if ($encode) {
			foreach ($to as &$str) {
				$str = $this->encodeHeader($str);
			}
			return implode(",", $to);
		} else {
			return $to;
		}
	}
	
	public function getHeader($name = null)
	{
		if ($name === null) {
			return $this->headers;
		}	
		return isset($this->headers[$name]) ? $this->headers[$name] : '';	
	}
	
	public function getHeaders($encode = true)
	{
		if ($this->attachments) {
			$this->setHeader('Content-Type', 'multipart/mixed; charset=UTF-8; boundary='.$this->boundary);
		} else {
			$this->setHeader('Content-Type', $this->content_type.'; charset=UTF-8');
		}
		if (!$encode) {
			return $this->headers;
		} else {
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
	}
	
	public function getFrom($encode = true)
	{
		$from = $this->getHeader('From');
		if ($encode) {
			return $this->encodeHeader($from);
		}
		return $from;
	}
	
	public function getBody($encode = false)
	{
		if ($encode) {
			if ($this->attachments) {
				$result  = "--".$this->boundary."\r\n";
				$result .= "Content-Type: ".$this->content_type."; charset=UTF-8\r\n";
				$result .= "Content-Transfer-Encoding: base64\r\n\r\n";
				$result .= rtrim(chunk_split(base64_encode($this->body)));
				foreach ($this->attachments as $attach) {
					$filename = mb_encode_mimeheader($attach[1], 'UTF-8', 'B');
					$result .= "\r\n--".$this->boundary."\r\n";
					$result .= "Content-Type: ".waFiles::getMimeType($attach[0]).";\r\n\tname=\"".$filename."\"\r\n";
					$result .= "Content-Disposition: ".(isset($attach[2]) ? "inline" : "attachment").";\r\n\tfilename=\"".$filename."\"\r\n";
					if (isset($attach[2])) {
						$result .= "Content-ID: <".$attach[2].">\r\n";
					}
					$result .= "Content-Transfer-Encoding: base64\r\n\r\n";					
					$result .= $this->encodeFile($attach[0])."\r\n";
				}
				$result .= "\r\n--".$this->boundary."--\r\n";
				return $result;
			} else {
				return rtrim(chunk_split(base64_encode($this->body)));
			}
		} else {
			return $this->body;
		}
	}
	
	public function addAttachment($file, $name = null, $inline = false)
	{
		if (!$name) {
			$name = basename($file);
		}
		$attach = array($file, $name);
		if ($inline) {
			$attach[] = md5($file.uniqid());
		}
		$this->attachments[] = $attach;
		$this->boundary = md5(uniqid(time()));
		if ($inline) {
			return 'cid:'.end($attach);
		}		
	}
	
	public function encodeHeader($value)
	{
		$value = trim($value);
		if (preg_match('/^<\S+@\S+>$/', $value)) {
			return $value;
        } else if (preg_match('/^\S+@\S+$/', $value)) {
			return $value;
		} else if (preg_match('/<*\S+@\S+>*$/', $value, $matches)) {
            $address = $matches[0];
            $name = str_replace($address, '', $value);
            $name = trim($name);
            if ($name) {
                if (!preg_match('/^[a-z0-9\s"-]+$/i', $name)) {
	                if (substr($name, 0, 1) == '"' && substr($name, -1) == '"') {
	                    $name = str_replace(array("\\\"", "\\\\"), array("\"", "\\"), $name);
	                    $name = substr($name, 1, -1);
	                }
	                $name = mb_encode_mimeheader($name, 'UTF-8', 'B');
                } else if ((substr($name, 0, 1) != '"' || substr($name, -1) != '"') && preg_match('/[\(\)\<\>\\\.\[\]@,;:"]/', $name)) {
                	$name = '"'.addcslashes($name, '\\"').'"';
                }                
                $address = preg_replace('/^<*(\S+@\S+?)>*$/', '<$1>', $address);
                return $name.' '.$address;
            }
            return $address;
        } else {
            return mb_encode_mimeheader($value, 'UTF-8', 'B');
        }	
	}
	
	/**
	 * Encode attachment using base64
	 * 
	 * @return string
	 */
	public function encodeFile($file) 
	{		
		if (!file_exists($file) || !is_readable($file)) {
			throw new waException("Could not open file ".$file, 602);
		}
		return chunk_split(base64_encode(file_get_contents($file)));
	}	
	
	
	public function send()
	{
		$mail = new waMail();
		return $mail->send($this);
	}
}