<?php
/**
 * Cron cli tasks description
 */
return array(
	//run every 30 minutes
	'scheduled_posts'=>'*/30 * * * * your/path/to/php5 '.wa()->getConfig()->getPath('root').DIRECTORY_SEPARATOR.'cli.php blog cronSchedule',
);