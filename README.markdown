# Webasyst Framework #

http://www.webasyst.com/

## Requirements ##

* PHP 5.2+
    * spl extension
    * mbstring
	* iconv
	* json
	* gd or ImageMagick extension

* MySQL 4.1+

## Installing Webasyst Framework ##

To install most recent version:
cd PATH
git clone git://github.com/webasyst/webasyst-framework.git ./

cd wa-config
cp apps.php.example apps.php
cp config.php.example config.php
cp db.php.example db.php
cp locale.php.example locale.php
cp SystemConfig.class.php.example SystemConfig.class.php

Create a database for Webasyst Framework.
Open db.php in a text editor and fill in your database details.

Run Webasyst Framework in a web-browser. 
    
