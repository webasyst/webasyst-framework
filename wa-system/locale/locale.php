<?php

class waGettextParser
{
	protected $config = array(
		'include' => '.*',
		'project' => 'WebAsyst',
		'path' => '/',
		'locales' => array(),
		'debug' => false
	);

	protected $words = array();

	public function __construct($config)
	{
		foreach ($config as $name => $value) {
			$this->config[$name] = $value;
		}
	}

	public function getFiles($dir, $context = "/")
	{
		if (!file_exists($dir)) {
			return array();
		}
		$result = array();
		$handler = opendir($dir);
		while ($file = readdir($handler)) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			if (is_dir($dir."/".$file)) {
				$result = array_merge($result, $this->getFiles($dir."/".$file, $context.$file."/"));
			} else {
				if (preg_match("/^".$this->config['include']."$/ui", $file)) {
					$result[] = array($context, $file);
				}
			}
		}
		return $result;
	}

	public function getWords($file)
	{
		$text = file_get_contents($file);
		$file = substr($file, strlen(realpath(dirname(__FILE__)."/../../")));
		$matches = array();
		if (preg_match_all("/\[\`([^\`]+)\`\]/usi", $text, $matches, PREG_OFFSET_CAPTURE)) {
			foreach ($matches[1] as $match) {
				$this->cache(array($match[0], $file.":".$this->getLine($text, $match[1])));
			}
		}
		$function_pattern = ($this->config['project'] == 'webasyst')?'_ws?':'_w';
		if (preg_match_all("/(?:{$function_pattern}|\$_)\(\"((\\\\\"|[^\"])+)\"\)/usi", $text, $matches, PREG_OFFSET_CAPTURE)) {
			foreach ($matches[1] as $match) {
				$this->cache(array($match[0], $file.":".$this->getLine($text, $match[1])));
			}
		}
		if (preg_match_all("/(?:{$function_pattern}|\$_)\('((\\\\'|[^'])+)'\)/usi", $text, $matches, PREG_OFFSET_CAPTURE)) {
			foreach ($matches[1] as $match) {
				$this->cache(array($match[0], $file.":".$this->getLine($text, $match[1])));
			}
		}
	}

	protected function getLine($text, $pos)
	{
		$lines = explode("\n", mb_substr($text, 0, $pos));
		return count($lines); //.":".mb_strlen(end($lines));
	}

	public function cache($words_info)
	{
		$this->words[$words_info[0]] = $words_info[1];
	}


	public function exec($sources)
	{
		foreach ($sources as $source) {
			$files = $this->getFiles($source);
			foreach ($files as $file) {
				if ($this->config['debug']) {
					echo $file[0].$file[1]."\r\n";
				}
				$this->getWords($source.$file[0].$file[1]);
			}
		}

		$this->save();
	}

	public function save()
	{
		foreach ($this->config['locales'] as $locale => $domain) {
			$locale_path = $this->config['path']."/".$locale."/"."LC_MESSAGES"."/".$domain.".po";
			if (!file_exists($locale_path)) {
				$this->create($locale);
			}
			$locale_content = file_get_contents($locale_path);
			if($fh = fopen($locale_path, "a+")){
				if ($this->config['debug']) {
					echo "\r\n".$locale_path." - ".count($this->words)." records\r\n";
				}
				flock($fh, LOCK_EX);
				foreach ($this->words as $words => $lines) {
					/* Ищем вхождения текущей фразы */
					if(strpos($locale_content, "msgid \"" . str_replace('"', '\\"',$words) . "\"") !== false) {
						continue;
					}

					/* Если не нашли - записываем */
					fputs($fh, "\n#: ".$lines."\n");
					fputs($fh, "msgid \"" . str_replace('"', '\\"', $words) . "\"\n");
					fputs($fh, "msgstr \"\"\n");
				}
				flock($fh, LOCK_UN);
				fclose($fh);
			}else{
				echo "\r\nError while open {$locale_path} in a+ mode\r\n";
			}
		}
	}


	public function create($locale)
	{
		$time = date("Y-m-d H:iO");
		if ($locale == 'ru_RU') {
			$plural = '
"Plural-Forms: nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));\n"';
		} else {
			$plural = '';
		}
		$text = <<<TEXT
msgid ""
msgstr ""		
"Project-Id-Version: {$this->config['project']}\\n"
"POT-Creation-Date: {$time}\\n"
"PO-Revision-Date: \\n"
"Last-Translator:  {$this->config['project']}\\n"
"Language-Team:  {$this->config['project']}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=utf-8\\n"
"Content-Transfer-Encoding: 8bit\\n"{$plural}
"X-Poedit-Language: {$locale}\\n"
"X-Poedit-SourceCharset: utf-8\\n"
"X-Poedit-Basepath: .\\n"
"X-Poedit-SearchPath-0: .\\n"
"X-Poedit-SearchPath-1: .\\n"

TEXT;

		$locale_path = $this->config['path']."/".$locale."/"."LC_MESSAGES";
		if (!file_exists($locale_path)) {
			mkdir($locale_path, 0777, true);
		}
		$locale_file = $locale_path."/".$this->config['locales'][$locale].".po";
		$f = fopen($locale_file, "w+");
		if (!$f) {
			throw new Exception("Could not create locale: ".$locale_file);
		}
		fwrite($f, $text);
		fclose($f);
	}
}


// start script

if (count($argv) < 2) {
	die("Usage: php locale.php APP_ID\n");
}

$app_id = $argv[1];
if ($app_id == 'webasyst') {
	$path = realpath(dirname(__FILE__)."/../../")."/wa-system/";
	$include = array(
	substr($path, 0, -1)
	);
} else {
	$path = realpath(dirname(__FILE__)."/../../")."/wa-apps/";
	$include = array(
	$path.$app_id."/templates",
	$path.$app_id."/lib",
	);
}

if (!file_exists($path)) {
	die("Application ".$app_id." does not exists\n");
}


$config = array(
	'project' => $app_id,
	'include' => ".+\.(html|js|php)",
	'path' => $path.$app_id."/locale",
	'locales' => array()
);

$locales = include(realpath(dirname(__FILE__)."/../../")."/wa-config/locale.php");
foreach ($locales as $l) {
	$config['locales'][$l] = $app_id;
}

if (isset($argv[2]) && $argv[2] == 'debug') {
	$config['debug'] = true;
}

$parser = new waGettextParser($config);

$parser->exec($include);
