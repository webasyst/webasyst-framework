<?php 

class siteDesignSaveController extends waJsonController
{
	public function execute()
	{
		$app = waRequest::get('app');
		if (!$app) {
		    $app = $this->getApp();
		}
		$theme_id = waRequest::get('theme');
		$file = waRequest::get('file');
		
		// create .htaccess to deny access to *.php and *.html files
		$path = wa()->getDataPath('themes', true, $app, false);
		if (!file_exists($path.'/.htaccess')) {
		    waFiles::create($path.'/');
            $htaccess = '<FilesMatch "\.(php\d?|html)">
    Deny from all
</FilesMatch>
';
            @file_put_contents($path.'/.htaccess', $htaccess);
		}

		$theme = new waTheme($theme_id, $app);
		if ($theme['type'] == waTheme::ORIGINAL) {
		    $theme->copy();
		}
		
		// create file
		if (!$file) {
			$file = waRequest::post('file');
			if (!$this->checkFile($file)) {
			    return;
			}
			if (!$theme->addFile($file, waRequest::post('description'))->save()) {
			    $this->errors = _w('Insufficient file access permissions to save theme settings');
			}
		} else {
		    if (waRequest::post('file') && $file != waRequest::post('file')) {
		    	if (!$this->checkFile($file)) {
		    		return;
		    	}
		    	$theme->removeFile($file);
		    	$file = waRequest::post('file');
		    	if (!$theme->addFile($file, waRequest::post('description'))->save()) {
		    	    $this->errors = _w('Insufficient file access permissions to save theme settings');		    	    
		    	}
		    } else {
		        if (!$theme->changeFile($file, waRequest::post('description'))) {
		            $this->errors = _w('Insufficient file access permissions to save theme settings');
		        }
		    }
		    @touch($theme->getPath().'/'.waTheme::PATH);
		}
				
		if ($file && !$this->errors) {
			// update mtime of theme.xml
		    @touch($path);
            $this->response['id'] = $file;
            $this->response['theme'] = $theme_id;				    
			$content = waRequest::post('content');
			$file_path = $theme->getPath().'/'.$file;
			if (!file_exists($file_path) || is_writable($file_path)) {
			    if (file_exists($file_path)) {
				    @file_put_contents($file_path, $content ? $content : '');
                    $r = true;
			    } else {
			        $r = @touch($file_path);
			    }
				if (!$r) {
					$this->errors = _w('Insufficient access permissions to save the file').' '.$file_path;
				}
			} else {
				$this->errors = _w('Insufficient access permissions to save the file').' '.$file_path;
			}
		} 
	}
	
	protected function checkFile($file)
	{
		if (!preg_match("/^[a-z0-9_\.-]+$/", $file)) {
		    $this->errors = array(
		    	_w('Only latin characters (a—z, A—Z), numbers (0—9) and underline character (_) are allowed.'), 
		    	'input[name=file]'
		    );
		    return false;
		} 
		if (!preg_match("/\.(xml|xsl|html|js|css)$/i", $file)) {
		    $this->errors = array(
		    	_w('File should have one of the allowed extensions:').' .html, .css, .js, .xml, .xsl',
		        'input[name=file]'
		    );
		    return false;
		}
	    return true;
	}
}