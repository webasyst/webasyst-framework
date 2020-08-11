# Webasyst #

Webasyst is an open-source PHP framework for fast development of web apps with a password-protected user backend and a publicly available website frontend.
Distributed under the terms of LGPL license.

Website: http://www.webasyst.com

## System Requirements ##

	* Web server
		* e.g., Apache, nginx, or IIS
		
	* PHP 5.6+
 
	* PHP extensions
		* spl
		* mbstring
		* iconv
		* json
		* gd or ImageMagick

	* MySQL 4.1+


## How to install Webasyst framework ##

1. Copy the source code to your web server's `%PATH%` directory (e.g., *public_html/webasyst*).

	git:
	```
	cd %PATH%
	git clone git://github.com/webasyst/webasyst-framework.git
	```

	SVN:
	```
	cd %PATH%
	svn checkout http://svn.github.com/webasyst/webasyst-framework.git
	```

2. Enable the framework installation directory (`%PATH%`) for writing.
	```
	cd ..
	chmod %PATH% 0775
	
	(or 0777 depending on your server configuration)
	```

3. Create a new MySQL database for Webasyst.

4. Open the URL of the installation directory in a browser; e.g., *http://localhost/webasyst/*. This will start a web-based installation wizard.

5. Complete all steps of the installation wizard.
    * On the database setup step, enter the credentials of the MySQL database created for Webasyst.
    * On the first user setup step, enter any user name, password, and email address for your main Webasyst user (administrator).
    * Sign into the user backend to complete the installation.

## How to update Webasyst framework ##

1. Update framework files from a repository.
2. Sign into your Webasyst user backend. This will automatically apply any required meta updates to the new framework version.
