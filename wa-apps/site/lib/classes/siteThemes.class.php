<?php
class siteThemes extends waTheme
{
	public static function load($themes, $domains = null, $app_id = null)
	{
		foreach($themes as $theme_id => &$theme) {
			self::prepare($theme);
			$theme['used'] = self::getRoutingRules($domains, $app_id, $theme_id);
			unset($theme);
		}
		return $themes;
	}

	public static function sort($themes)
	{
		uasort($themes, array(__CLASS__,'sortThemesHandler'));
		return $themes;
	}

	private static function getRoutingRules($domains,$app_id,$theme_id)
	{
		static $themes;
		if (!is_array($themes)) {
			$themes = array();
			$theme_types = array('desktop'=>'theme','mobile'=>'theme_mobile');
			$routing = wa()->getRouting();
			foreach( (array)$domains as $domain) {
				$routing_params = array('domain' => $domain);//,'app'=>$app_id);
				$rules = $routing->getRoutes($domain);
				foreach ($rules as $route_id => $rule) {
					if (isset($rule['app'])) {
						foreach($theme_types as $type=>$source) {
							$id = isset($rule[$source])?$rule[$source]:'default';
							$app = $rule['app'];
							if (!isset($themes[$app])) {
								$themes[$app] = array();
							}
							if (!isset($themes[$app][$id])) {
								$themes[$app][$id] = array();
							}
							$themes[$app][$id][] = array(
							'domain'	=> $domain,
							'url'		=> $rule['url'],
							'type'		=> $type,
							'preview'	=> $routing->getUrlByRoute($rule, $domain)
							);
						}

					}
				}
			}
		}
		return isset($themes[$app_id][$theme_id])?$themes[$app_id][$theme_id]:false;
	}

	private static function sortThemesHandler($theme1, $theme2)
	{
		return min(1,max(-1,$theme2['mtime'] - $theme1['mtime']));
	}

	/**
	 *
	 * @param $slug
	 * @return siteThemes
	 */
	public static function getInstance($slug)
	{
		$slug = urldecode($slug);
		if(preg_match('@^/?([a-z_0-9]+)/themes/([a-zA-Z_0-9\-]+)/?$@',$slug,$matches)){
			return new self($matches[2],$matches[1]);
		}else {
			throw new waException(_w('Invalid theme slug').$slug);
		}
	}

	private static function prepare(&$theme)
	{
		static $root;
		if (!$root) {
			$root = wa()->getConfig()->getRootPath();
		}
		$theme['name'];
		if(!isset($theme['preview'])) {
			$theme['preview'] = false;
		}
		if(!isset($theme['used'])) {
			$theme['used'] = false;
		}

		if ($theme['path_custom'] && (strpos($theme['path_custom'],$root) === 0)) {
			$theme['custom'] = str_replace($root,'',$theme['path_custom']);
			$theme['custom'] = preg_replace(array('@[\\\\/]+@','@^/@'),array('/',''),$theme['custom']);
		}

		if ($theme['path_original'] && (strpos($theme['path_original'],$root) === 0)) {
			$theme['original'] = str_replace($root,'',$theme['path_original']);
			$theme['original'] = preg_replace(array('@[\\\\/]+@','@^/@'),array('/',''),$theme['original']);
		}

		if (isset($theme['app'])) {
			//$theme['app_id'] = $theme['app'];
			$theme['slug'] = $theme['app'].'/themes/'.$theme['id'];
		}
	}


	public function getInfo($domains = null)
	{
		//$info = array_merge($this->info,get_object_vars($this));
		self::prepare($this);
		$this->extra_info['used'] = self::getRoutingRules($domains, $this->app, $this->id);
		return $this;
	}

	public static function exists($id, $app = true, $force = false)
	{
		self::verify($id);
		$app = ($app === true) ? wa()->getApp() : $app;
		$path_custom	 = wa()->getDataPath('themes', true, $app).'/'.$id;
		$path_original = wa()->getAppPath('themes/', $app).$id;
		if (!file_exists($path_custom) || (!$force && !file_exists($path_custom.'/'.self::PATH))) {
			$path_custom = false;
		}

		if (!file_exists($path_original) || (!$force && !file_exists($path_original.'/'.self::PATH))) {
			$path_original = false;
		}
		return  ($path_custom || $path_original)?true:false;
	}

	public function check()
	{
		if(!$this->path) {
			throw new waException(sprintf(_w("Theme %s not found"),$this->id));
		}
		if(!file_exists($this->path) || !file_exists($this->path.'/'.self::PATH)) {
			self::throwThemeException('MISSING_THEME_XML',$this->id);
		}
		//TODO check files of theme
	}


	/**
	 *
	 * @return siteThemes
	 */
	public function brush()
	{
		//TODO check theme type
		waFiles::delete($this->path_custom,false);
		$this->flush();
		$instance = new self($this->id,$this->app);
		return $instance;
	}

	public function purge()
	{
		//TODO check theme type
		waFiles::delete($this->path_custom);
		//waFiles::delete($this->original);
		$this->flush();
	}

	public function duplicate()
	{
		$numerator = 0;
		do {
			$id = $this->id.++$numerator;
			if($numerator>1000){
				break;
			}
		} while($available = self::exists($id,$this->app,true));
		
		if($available) {
			throw new waException(_w("Duplicate theme failed"));
		}
		$names = $this->getName(true);
		foreach($names as &$name) {
			$name .= ' '.$numerator;
		}
		unset($name);
		return $this->copy($this->id.$numerator,array('name'=>$names));
	}

	/**
	 *
	 * Extract theme from archive
	 * @throws Exception
	 * @param string $source_path archive path
	 *
	 * @return siteThemes
	 */
	public static function extract($source_path)
	{

		$autoload = waAutoload::getInstance();
		$autoload->add('Archive_Tar','wa-installer/lib/vendors/PEAR/Tar.php');
		$autoload->add('PEAR','wa-installer/lib/vendors/PEAR/PEAR.php');
		if (class_exists('Archive_Tar')) {
			try {
				$tar_object= new Archive_Tar($source_path,true);
				$files = $tar_object->listContent();
				if(!$files) {
					self::throwArchiveException('INVALID_OR_EMPTY_ARCHIVE');
				}

				//search theme info
				$theme_check_files = array(self::PATH,);
				$theme_files_map = array();
				$info = false;
				$pattern = "/(\/|^)".wa_make_pattern(self::PATH)."$/";
				foreach($files as $file) {
					if (preg_match($pattern,$file['filename'])) {
						$info = $tar_object->extractInString($file['filename']);
						break;
					}
				}

				if(!$info) {
					self::throwThemeException('MISSING_THEME_XML');
				}

				$xml = @simplexml_load_string($info);
				$app_id = (string)$xml['app'];
				$id = (string)$xml['id'];

				if (!$app_id) {
					self::throwThemeException('MISSING_APP_ID');
				} elseif(!$id) {
					self::throwThemeException('MISSING_THEME_ID');
				} else {
					if($app_info = wa()->getAppInfo($app_id)) {
						//TODO check theme support
					} else {
						$message = sprintf(_w('Theme “%s” is for app “%s”, which is not installed in your Webasyst. Install the app, and upload theme once again.'),$id,$app_id);
						throw new waException($message);
					}
				}



				$wa_path = "wa-apps/{$app_id}/themes/{$id}";
				$wa_pattern = wa_make_pattern($wa_path);
				$file = reset($files);
				if(preg_match("@^{$wa_pattern}(/|$)@",$file['filename'])) {
					$extract_path = $wa_path;
					$extract_pattern = $wa_pattern;
				} else {
					$extract_path = $id;
					$extract_pattern = wa_make_pattern($id);
					if(!preg_match("@^{$extract_pattern}(/|$)@",$file['filename'])) {
						$extract_path = '';
						$extract_pattern = false;
					}
				}

				foreach($files as $file) {
					if($extract_pattern && !preg_match("@^{$extract_pattern}(/|$)@",$file['filename'])) {
						self::throwThemeException('UNEXPECTED_FILE_PATH',"{$file['filename']}. Expect files in [{$extract_path}] directory");
					} elseif(preg_match('@\.(php\d*|pl)@', $file['filename'],$matches)) {
						self::throwThemeException('UNEXPECTED_FILE_TYPE',$file['filename']);
					}
				}

				self::verify($id);
				self::protect($app_id);
				$target_path = wa()->getDataPath("themes/{$id}",true,$app_id,false);
				waFiles::delete($target_path);
				if ($extract_path && !$tar_object->extractModify($target_path, $extract_path)) {
					self::throwArchiveException('INTERNAL_ARCHIVE_ERROR');
				} elseif(!$tar_object->extract($target_path)) {
					self::throwArchiveException('INTERNAL_ARCHIVE_ERROR');
				}
			} catch (Exception $ex) {
				if(isset($target_path) && $target_path) {
					waFiles::delete($target_path, true);
				}
				throw $ex;
			}
		} else {
			self::throwArchiveException('UNSUPPORTED_ARCHIVE_TYPE');
		}
		$instance =  new self($id,$app_id);
		$instance->check();
		return $instance;
	}
	
	private static function throwThemeException($code, $details = '')
	{
		$link = sprintf(_w('http://www.webasyst.com/framework/docs/site/themes/#%s'),$code);
		$message = $code.($details?", {$details}":'');
		throw new waException(sprintf(_w("Invalid theme archive structure (%s). <a href=\"%s\" target=\"_blank\">See help</a> for details"),$code,$link));
	}
	
	private static function throwArchiveException($code, $details = '')
	{
		$link = sprintf(_w('http://www.webasyst.com/framework/docs/site/themes/#%s'),$code);
		$message = $code.($details?", {$details}":'');
		throw new waException(sprintf(_w("Failed to extract files from theme archive (%s). <a href=\"%s\" target=\"_blank\">See help</a> for details"),$code,$link));
	}

	/**
	 *
	 * Compress theme into archive file
	 * @param string $path target archive path
	 * @param string $name archive filename
	 * @return string arcive path
	 */
	public function compress($path, $name = null)
	{
		if(!$name) {
			$name = "webasyst.{$this->app}.theme.{$this->id}.tar.gz";
		}
		$target_file = "{$path}/{$this->app}/{$name}";

		$autoload = waAutoload::getInstance();
		$autoload->add('Archive_Tar','wa-installer/lib/vendors/PEAR/Tar.php');
		$autoload->add('PEAR','wa-installer/lib/vendors/PEAR/PEAR.php');

		if(file_exists($this->path) && class_exists('Archive_Tar',true)) {
			waFiles::create($target_file);
			$tar_object = new Archive_Tar( $target_file, true );
			$tar_object->setIgnoreRegexp('@(\.(php\d?|svn|git|fw_|files\.md5$))@');
			$path = getcwd();
			chdir(dirname($this->path));
			if (!$tar_object->create('./'. basename($this->path) )) {
				waFiles::delete($target_file);
			}
			chdir($path);
		}
		return $target_file;
	}
}
//EOF