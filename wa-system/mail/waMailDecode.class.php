<?php

class waMailDecode
{
	protected $options = array(
		'buffer_size' => 16384,
		'headers_only' => false,
		'attach_path' => ''
	);
	
	const STATE_START = 1;
	const STATE_HEADER = 2;
	const STATE_HEADER_VALUE = 3;
	const STATE_PART = 4;
	const STATE_PART_HEADER = 5;
	const STATE_PART_DATA = 6;	
	const STATE_END = 7;
	
	const TYPE_HEADER = 1;
	const TYPE_HEADER_VALUE = 2;
	const TYPE_PART = 3;
	const TYPE_ATTACH = 4;
	
	protected $source;
	protected $state;
	
	protected $buffer = '';
	protected $buffer_offset = 0;
	
	protected $attachments = array();
	
	protected $parts = array();
	protected $part;
	protected $part_index = 0;
	protected $is_last = false;
	
	protected $body = array(
	);
	
	protected $current_header;
	
	public function __construct($options = array())
	{
		$this->options = $options + $this->options;	
	}
	
	public function decode($file, $full_response = false)
	{		
		if (is_resource($file)) {
			$this->source = $file;
		} else {
			$this->source = fopen($file, 'r');
			$this->options['attach_path'] = dirname($file).'/files/';
		}
		// start state
		$this->buffer = '';
		$this->buffer_offset = 0;
		$this->is_last = false;		
		$this->state = self::STATE_HEADER;
		$this->parts = array(array());
		$this->attachments = array();
		$this->body = array();
		$this->part = &$this->parts[0];
		// check end of file
		if (!feof($this->source)) {
			$part = false;
			while ($this->state != self::STATE_END) {
				if (!$part) {
					if ($this->is_last) {
						throw new waException("Письмо не было завершено, а данные в файле уже кончились.");		
					}
					$this->read();
				}
				$part = $this->parse();
				if ($part && is_array($part)) {
					$this->decodePart($part);
				}
			}	
			if (!$this->is_last) {
				if (!$this->options['headers_only']) {
					throw new waException("Конец письма уже достигнут. Есть какие-то данные еще.");
				}	
			}
		}
		fclose($this->source);
		
		$result = array();
		
		$headers = array('subject','from', 'to', 'cc', 'bcc', 'reply-to');
		foreach ($headers as $h) {
			if (isset($this->parts[0]['headers'][$h])) {
				$result[$h] = $this->decodeHeader($this->parts[0]['headers'][$h]);
				if (is_array($result[$h])) { 
					$result[$h] = implode("", $result[$h]);	
				}
				if ($h != 'subject' && $h != 'from') {
					$result[$h] = $this->explodeEmails($result[$h]);
				}
			} else {
				$result[$h] = '';
			}
		}
		
		$i = (int)strrpos($result['from'], ' ');
		if (($j = strrpos($result['from'], '<')) !== false) {
			if ($j > $i) {
				$i = $j;
			}
		}
		
		$result['from'] = array(
			'name' => trim(substr($result['from'], 0, $i), ' "'),
			'email' => trim(substr($result['from'], $i + 1), ' <>')
		);
		
		if (strpos($result['subject'], ' ') === false) {
			$result['subject'] = str_replace('_', ' ', $result['subject']);
		}
		if (isset($this->parts[0]['headers']['date'])) {
			$d = preg_replace("/[^a-z0-9:,\.\s\t\+]/i", '', $this->parts[0]['headers']['date']);
			$result['date'] = date("Y-m-d H:i:s", strtotime($d));
		} else {
			$result['date'] = '';
		}
		// return body		
		$result['html'] = $result['plain'] = '';
		if (isset($this->body['html'])) {
			$result['html']	= $this->cleanHTML($this->body['html']);
			if (!isset($this->body['plain']) || ($this->body['html'] && !trim($this->body['plain']))) {
				$result['plain'] = trim(strip_tags($result['html']));	
			}
		}
		if (isset($this->body['plain']) && !$result['plain']) {
			$result['plain'] = trim($this->body['plain']);
			if (!isset($this->body['html'])) {
				$result['html'] = nl2br($result['plain']);	
			}			
		}
		// return attachments
		$result['attachments'] = $this->attachments;
		if ($full_response) {
			$result['parts'] = $this->parts;
		}
		return $result;
	}	
	
	protected function cleanHTML($html)
	{
		// remove tags
		$html = trim(strip_tags($html, "<a><p><div><br><b><strong><i><em><s><u><span><img><sup><font><sub><ul><ol><li><h1><h2><h3><h4><h5><h6><table><tr><td><th><hr><center>"));
    	// realign javascript href to onclick 
	    $html = preg_replace("/href=(['\"]).*?javascript:(.*)?\\1/i", "onclick=' $2 '", $html);
	    return $html; 
	    //remove javascript from tags 
	    while (preg_match("/<(.*)?javascript.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", $html)) {
	        $html = preg_replace("/<(.*)?javascript.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", "<$1$3$4$5>", $html); 
	    }
	    
	    // dump expressions from contibuted content 
	    $html = preg_replace("/:expression\(.*?((?>[^(.*?)]+)|(?R)).*?\)\)/i", "", $html); 
		
	    while (preg_match("/<(.*)?:expr.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", $html)) { 
	        $html = preg_replace("/<(.*)?:expr.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", "<$1$3$4$5>", $html);
	    } 
	    // remove all on* events    
	    while (preg_match("/<(.*)?[\s\r\n\t]on.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", $html)) { 
	       $html = preg_replace("/<(.*)?[\s\r\n\t]on.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", "<$1$3>", $html); 
	    }
	    return $html;		
	}
	
	protected function explodeEmails($string)
	{
		$result = array();
		$email = '';
		$data = strtok($string, ',');
		do {
			$email .= $data;
			if (strpos($email, '@') !== false && 
			   (strpos($email, '"') === false || (strpos($email, '"') !== strrpos($email, '"')))
			) {
				$result[] = trim($email);
				$email = '';
			} 
		} while ($data = strtok(','));
		return $result;
	}
	
	protected function read()
	{
		$this->buffer .= fread($this->source, $this->options['buffer_size']);
		$this->is_last = feof($this->source);
	}
	
	
	protected function parse()
	{
		switch ($this->state) {
			case self::STATE_HEADER:
				if ($this->buffer[$this->buffer_offset] == "\n" || substr($this->buffer, $this->buffer_offset, 2) == "\r\n") {
					$this->state = self::STATE_PART;
					return true;	
				}				
				if (substr($this->buffer, $this->buffer_offset, 5) === 'From ') {
					$this->buffer_offset += 5;
					$this->state = self::STATE_HEADER_VALUE;
					return array(
						'type' => self::TYPE_HEADER,
						'value' => 'from ',
					);
				}
				if (($i = strpos($this->buffer, ':', $this->buffer_offset)) !== false) {
					// next state
					$value = substr($this->buffer, $this->buffer_offset, $i - $this->buffer_offset);
					$this->buffer_offset = $i + 1;
					$this->state = self::STATE_HEADER_VALUE;
					// return part info
					return array(
						'type' => self::TYPE_HEADER,
						'value' => strtolower(trim($value))
					);
				} else {
					// need more data
					return false;
				}
			case self::STATE_HEADER_VALUE:
				$offset = $this->buffer_offset;
				$value = '';
				while (true) {
					if (($i = strpos($this->buffer, "\n", $offset)) !== false) {
						$i++;
						$value .= substr($this->buffer, $offset, $i - $offset);
						// multiline
						if ($this->buffer[$i] == ' ' || $this->buffer[$i] == "\t") {
							$offset = $i;
						} else {
							// next header
							$this->buffer_offset = $i;
							$this->state = self::STATE_HEADER;
							return array(
								'type' => self::TYPE_HEADER_VALUE,
								'value' => trim($value)
							);
						}
					} else {
						// need more data
						return false;
					}
				}
			case self::STATE_PART:
				if ($this->options['headers_only']) {
					$this->state = self::STATE_END;
					return false;
				}				
				if (!isset($this->part['type'])) {
					$this->part['type'] = 'text';
					$this->part['headers']['content-transfer-encoding'] = 'quoted-printable';
				}
				if ($this->part['type'] == 'multipart') {
					$boundary = '--'.$this->part['params']['boundary'];
					if (($i = strpos($this->buffer, $boundary, $this->buffer_offset)) !== false) {
						if (strlen($this->buffer) < $i + strlen($boundary)) {
							return false;
						}
						$this->buffer_offset = $i + strlen($boundary);
						if (substr($this->buffer, $this->buffer_offset, 2) == "--") {
							if (isset($this->part['parent'])) {
								$this->part_index = $this->part['parent'];
								$this->part = &$this->parts[$this->part_index];
								$this->buffer_offset += 2;
								$this->skipLineBreak();
								return true;		
							} else {
								$this->state = self::STATE_END;
							}
							return false;
						}
						$this->skipLineBreak();
						$this->parts[] = array('parent' => $this->part_index);
						$this->part_index = count($this->parts) - 1;
						$this->part = &$this->parts[$this->part_index];
						$this->state = self::STATE_HEADER;	
					} else {
						return false;
					}
				} else {
					$this->state = self::STATE_PART_DATA;
				}
				return true;
			case self::STATE_PART_DATA:
				if (isset($this->part['parent'])) {
					// save applications 
					if ($this->attachments || $this->part['type'] != 'text' || isset($this->part['headers']['content-disposition'])) {
						return array(
							'type' => self::TYPE_ATTACH,
							'value' => $this->buffer_offset,
							'boundary' => $this->parts[$this->part['parent']]['params']['boundary']
						);
					}
					// other parts
					$boundary = "\n--".$this->parts[$this->part['parent']]['params']['boundary'];
					if (($i = strpos($this->buffer, $boundary, $this->buffer_offset)) === false) {
						// need more data
						return false;
					}
					$value = substr($this->buffer, $this->buffer_offset, $i - $this->buffer_offset);
					$this->buffer_offset = $i;
					$this->state = self::STATE_PART;
					return array(
						'type' => self::TYPE_PART,
						'value' => $value
					);
				} else {
					if (!$this->is_last) {
						return false;
					}
					$this->state = self::STATE_END;
					return array(
						'type' => self::TYPE_PART,
						'value' => substr($this->buffer, $this->buffer_offset)
					);
				}
		}
	}
	
	protected function skipLineBreak()
	{
		if ($this->buffer[$this->buffer_offset] == "\n") {
			$this->buffer_offset++;	
		} elseif (substr($this->buffer, $this->buffer_offset, 2) == "\r\n") {
			$this->buffer_offset += 2;
		}
	}
	
	protected function decodePart($part)
	{
		switch ($part['type']) {
			case self::TYPE_HEADER:
				$this->current_header = $part['value'];
				if (!isset($this->part['headers'][$part['value']])) {
					$this->part['headers'][$part['value']] = '';
				}
				break;
			case self::TYPE_HEADER_VALUE:
				if ($this->current_header == 'content-type') {
					$info = $this->parseHeader($part['value']);
					$this->part['type'] = strtolower(strtok($info['value'], '/'));
					$this->part['subtype'] = strtolower(strtok(''));
					$this->part['params'] = $info['params'];
					unset($info);
				} 
				if ($this->current_header === 'from ') {
					$this->current_header = 'from';
					unset($this->part['headers']['from ']);
					$this->part['headers']['from'] = strtok($part['value'], ' ');
					$this->part['headers']['date'] = strtok('');
				} elseif (strpos($part['value'], "\n") === false) {
					if ($part['value'] || !isset($this->part['headers'][$this->current_header])) {
						$this->part['headers'][$this->current_header] = $part['value'];
					}
				} else {
					$this->part['headers'][$this->current_header] = array(trim(strtok($part['value'], "\n")));
					while (($value = strtok("\n")) !== false) {
						$this->part['headers'][$this->current_header][] = trim($value);	
					}
				}
				break;
			case self::TYPE_ATTACH:
				$boundary = "\n--".$part['boundary'];
				if (!file_exists($this->options['attach_path'])) {
					waFiles::create($this->options['attach_path']);
				}
				$path = $this->options['attach_path'].(count($this->attachments) + 1);
				if (isset($this->part['params']['name'])) {
					if (($i = strrpos($this->part['params']['name'], '.')) !== false) {
						$path .= substr($this->part['params']['name'], $i);
					}
				} elseif ($this->part['type'] == 'image' && in_array($this->part['subtype'], array('gif', 'jpg', 'png'))) {
					$path .= '.'.$this->part['subtype'];
				}
				$attach = array(
					'file' => basename($path)
				); 
				if (isset($this->part['params']['name'])) {
					$attach['name'] = $this->part['params']['name'];
				}
				$attach['type'] = $this->part['type'];
				if (isset($this->part['subtype']) && $this->part['subtype']) {
					$attach['type'] .= '/'.$this->part['subtype'];
				}
				if (isset($this->part['headers']['content-id'])) {
					$attach['content-id'] = $this->part['headers']['content-id'];
					if (substr($attach['content-id'], 0, 1) == '<') {
						$attach['content-id'] = substr($attach['content-id'], 1);
					}
					if (substr($attach['content-id'], -1) == '>') {
						$attach['content-id'] = substr($attach['content-id'], 0, -1);
					}					
				}
				$this->attachments[] = $attach;
				unset($attach);
				$fp = fopen($path, "w+");
				if (isset($this->part['headers']['content-transfer-encoding'])) {
					if ($this->part['headers']['content-transfer-encoding'] == 'base64') {
						stream_filter_append($fp, "convert.base64-decode", STREAM_FILTER_WRITE);
					} elseif ($this->part['headers']['content-transfer-encoding'] == 'quoted-printable') {
						stream_filter_append($fp, "convert.quoted-printable-decode", STREAM_FILTER_WRITE);
					}
				}
				while (($i = strpos($this->buffer, $boundary, $this->buffer_offset)) === false && !$this->is_last) {
					fwrite($fp, $this->buffer_offset ? substr($this->buffer, $this->buffer_offset) : $this->buffer);
					$this->buffer = '';
					$this->buffer_offset = 0;
					$this->read();
				}
				fwrite($fp, substr($this->buffer, $this->buffer_offset, $i - $this->buffer_offset));
				fclose($fp);
				$this->buffer_offset = $i;
				$this->state = self::STATE_PART;
				if (isset($this->part['parent'])) {
					$this->part_index = $this->part['parent'];
					$this->part = &$this->parts[$this->part['parent']];
				}
				break;
			case self::TYPE_PART:
				switch ($this->part['type']) {
					case 'text':
						$this->part['data'] = $part['value'];
						unset($part);
						
						if (isset($this->part['headers']['content-transfer-encoding'])) {
							switch ($this->part['headers']['content-transfer-encoding']) {
								case 'base64':
									$this->part['data'] = base64_decode($this->part['data']);
									break;	
								case 'quoted-printable':
									$this->part['data'] = preg_replace("/=\r?\n/", '', $this->part['data']);
									$this->part['data'] = preg_replace('/=([a-f0-9]{2})/ie', "chr(hexdec('\\1'))", $this->part['data']);
									break;									
							}
						}
						
						if (isset($this->part['params']['charset']) && strtolower($this->part['params']['charset']) != 'utf-8') {
							$this->part['data'] = iconv($this->part['params']['charset'], "utf-8", $this->part['data']);
						} else {
							$charset = mb_detect_encoding($this->part['data']);
							if ($charset && strtolower($charset) != "UTF-8" && $temp = iconv($charset, 'UTF-8', $this->part['data'])) {
								$this->part['data'] = $temp;
								unset($temp);
							} elseif (!$charset && !preg_match("//u", $this->part['data'])) {
								$temp = iconv("windows-1251", "utf-8", $this->part['data']);
								if (preg_match("/[а-я]/ui", $temp)) {
									$this->part['data'] = $temp;
								}
								unset($temp);
							}
						}
						$this->body[$this->part['subtype']] = $this->part['data'];
				}
				if (isset($this->part['parent'])) {
					$this->part_index = $this->part['parent'];
					$this->part = &$this->parts[$this->part['parent']];
				}				
				$this->clearBuffer();
		}
	}
	
	protected function clearBuffer()
	{
		$this->buffer = substr($this->buffer, $this->buffer_offset);
		$this->buffer_offset = 0;
	}
	
	protected function decodeHeader($value)
	{
		if (is_array($value)) {
			foreach ($value as &$v) {
				$v = $this->decodeHeader($v);
			}
			unset($v);
			return $value;
		}
		if (preg_match("/=\?(.+)\?(B|Q)\?(.+)\?=?(.*)/i", $value)) {
			$temp = mb_decode_mimeheader($value);
			if ($temp === $value) {
				$value = iconv_mime_decode($value, 0, 'UTF-8');
			} else {
				$value = $temp;
			}
		} elseif (isset($this->part['params']['charset'])) {
			$value = iconv($this->part['params']['charset'], 'UTF-8', $value);
		}
		if (!preg_match('//u', $value)) {
			$charset = mb_detect_encoding($value);
			if ($charset && $temp = iconv($charset, 'UTF-8', $value)) {
				$value = $temp;
			}
		}
		return $value;		
	}	
	
	protected function parseHeader($str) 
	{
		if (is_array($str)) {
			$str = implode("", $str);
		}
		$result = array('value' => trim(strtok($str, ';')), 'params' => array());
		while (($param = strtok('=')) !== false) {
			$result['params'][strtolower(trim($param))] = trim(strtok(';'), ' "');
		}
		if (isset($result['params']['name'])) {
			$result['params']['name'] = $this->decodeHeader($result['params']['name']);
		}
		if (isset($result['params']['name*']) && !isset($result['params']['name'])) {
			$temp = explode("''", $result['params']['name*']);
			if (count($temp) == 2) {
				$result['params']['name'] = iconv($temp[0], 'UTF-8', urldecode($temp[1]));
				unset($result['params']['name*']);	
			}
		}
		return $result;
	}
		
}