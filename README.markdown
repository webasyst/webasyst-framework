# Webasyst #

Webasyst is an open source PHP Framework for developing web apps with backend and frontend.
Distributed under the terms of LGPL license.
http://www.webasyst.com/

## System Requirements ##

	* Web Server
		* e.g. Apache or IIS
		
	* PHP 5.2+
		* spl extension
		* mbstring
		* iconv
		* json
		* gd or ImageMagick extension

	* MySQL 4.1+


## Installing Webasyst Framework ##

1. Get the code into your web server's folder %PATH% (e.g. public_html/webasyst):

	via GIT:

		cd %PATH%
		git clone git://github.com/webasyst/webasyst-framework.git

	via SVN:
	
		cd %PATH%
		svn checkout http://svn.github.com/webasyst/webasyst-framework.git

2. Set up Webasyst config files (located within %PATH%/wa-config folder).

		cd wa-config
		cp apps.php.example apps.php
		cp config.php.example config.php
		cp SystemConfig.class.php.example SystemConfig.class.php

2. Enable framework installation folder (PATH) for writing

		cd ..
		chmod %PATH% 0775
		
		(or 0777 depending on server configuration)

3. MySQL Database:

	Create a new database for Webasyst Framework and its apps.

4. Run Webasyst Framework in a web browser (e.g. http://localhost/webasyst/).

## Updating Webasyst Framework ##

Staying with the latest version of Webasyst Framework is easy: simply update your files from the repository and login into Webasyst, and all required meta updates will be applied to Webasyst and its apps automatically.