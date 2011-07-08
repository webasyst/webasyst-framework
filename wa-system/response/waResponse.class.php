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
 * @subpackage response
 */
class waResponse {
	
	protected $cookies = array();
	protected $headers = array();
	protected $metas = array();
	protected $js = array();
	protected $css = array();
	
	protected $status;
	
    protected static $statuses = array(
	    '100' => 'Continue',
	    '101' => 'Switching Protocols',
	    '200' => 'OK',
	    '201' => 'Created',
	    '202' => 'Accepted',
	    '203' => 'Non-Authoritative Information',
	    '204' => 'No Content',
	    '205' => 'Reset Content',
	    '206' => 'Partial Content',
	    '300' => 'Multiple Choices',
	    '301' => 'Moved Permanently',
	    '302' => 'Found',
	    '303' => 'See Other',
	    '304' => 'Not Modified',
	    '305' => 'Use Proxy',
	    '306' => '(Unused)',
	    '307' => 'Temporary Redirect',
	    '400' => 'Bad Request',
	    '401' => 'Unauthorized',
	    '402' => 'Payment Required',
	    '403' => 'Forbidden',
	    '404' => 'Not Found',
	    '405' => 'Method Not Allowed',
	    '406' => 'Not Acceptable',
	    '407' => 'Proxy Authentication Required',
	    '408' => 'Request Timeout',
	    '409' => 'Conflict',
	    '410' => 'Gone',
	    '411' => 'Length Required',
	    '412' => 'Precondition Failed',
	    '413' => 'Request Entity Too Large',
	    '414' => 'Request-URI Too Long',
	    '415' => 'Unsupported Media Type',
	    '416' => 'Requested Range Not Satisfiable',
	    '417' => 'Expectation Failed',
	    '500' => 'Internal Server Error',
	    '501' => 'Not Implemented',
	    '502' => 'Bad Gateway',
	    '503' => 'Service Unavailable',
	    '504' => 'Gateway Timeout',
	    '505' => 'HTTP Version Not Supported',
  	);
	
	public function setCookie($name, $value, $expire = null, $path = null, $domain = '', $secure = false, $http_only = false)
  	{
    	if ($expire !== null) {
      		$expire = (int) $expire;
    	}
    	
    	if ($path === null) {
    		$path = waSystem::getInstance()->getRootUrl();
    	}
    	
    	$this->cookies[$name] = array(
	      'name'     => $name,
	      'value'    => $value,
	      'expire'   => $expire,
	      'path'     => $path,
	      'domain'   => $domain,
	      'secure'   => $secure ? true : false,
	      'http_only' => $http_only,
	    );
	    setcookie($name, $value, $expire, $path, $domain, $secure, $http_only);
  	}
	
  	
  	public function setStatus($code)
  	{
  		if (isset(self::$statuses[$code])) {
  			return $this->status = $code;
  		} else {
  			return false;
  		}
  	}
  	
  	public function addHeader($name, $value, $replace = true)
  	{
  		switch ($name) {
  			case 'Last-Modified': 
  				$value = gmdate("D, d M Y H:i:s", strtotime($value))." GMT";
  				break;
  		}
  		if ($replace || !isset($this->headers[$name])) {
			$this->headers[$name] = $value;
  		}
  	}
  	
  	public function getHeader($name = null)
  	{
  		return $name !== null ? $this->headers[$name] : $this->headers;
  	}
  	
  	public function redirect($url, $code = null)
  	{
  		if ($code !== null) {
  			$this->setStatus($code);
  		}
  		$this->addHeader('Location', $url);
  		$this->sendHeaders();
  		exit;
  	}
  	
  	public function sendHeaders()
  	{
  		if ($this->status !== null) {
  			header('HTTP/1.0 '.$this->status.' '.self::$statuses[$this->status]);
  		}
  		foreach ($this->headers as $name => $value) {
  			header($name. ": ". $value);
  		}
  	}
  	
  	public function getTitle()
  	{
  		return $this->getMeta('title');
  	}
  	
  	public function setTitle($title)
  	{
  		$this->setMeta('title', $title);
  	}
  	
  	public function setMeta($name, $value) 
  	{
  		$this->metas[$name] = $value;
  	}
  	
  	public function getMeta($name = null)
  	{
  		if ($name !== null) {
  		    return isset($this->metas[$name]) ? $this->metas[$name] : null; 
  		} else {
  			return $this->metas;
  		}
  	}  	
  	
  	public function addJs($url)
  	{
  		$this->js[] = $url;
  	}
  	
  	public function getJs($html = true)
  	{
  		if (!$html) {
  			return $this->js;
  		} else {
  			$result = '';
  			foreach ($this->js as $url) {
  				$result .= '<script type="text/javascript" src="'.$url.'"></script>'."\n";
  			}
  			return $result;
  		}
  	}
  	
  	public function addCss($url)
  	{
  		$this->css[] = $url;
  	}
  	
  	public function getCss($html = true)
  	{
  		if (!$html) {
  			return $this->css;
  		} else {
  			$result = '';
  			foreach ($this->css as $url) {
  				$result .= '<link href="'.$url.'" rel="stylesheet" type="text/css" >'."\n";
  			}
  			return $result;
  		}
  	}
  	
}